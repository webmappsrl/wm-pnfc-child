<?php
/**
 * oc:8940 — logica della migrazione dei social dal blocco Gutenberg core/social-links ai meta
 * delle guide (_guide_facebook, _guide_instagram, …). Usata da migrate-social-links.php e dai
 * controlli in check-migration.php. Richiede le funzioni wm_pnfc_* del child theme.
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}

const OC8940_BEFORE_FILE = 'social-migration-before.json';

/** Domini riconosciuti per spostare un social scritto nel campo «Url:» (_guide_sito). */
function oc8940_network_for_url(string $url): ?string
{
    $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    $domains = [
        'facebook'  => ['facebook.com'],
        'instagram' => ['instagram.com'],
        'linkedin'  => ['linkedin.com'],
        'x'         => ['x.com', 'twitter.com'],
        'youtube'   => ['youtube.com'],
        'tiktok'    => ['tiktok.com'],
    ];
    foreach ($domains as $network => $list) {
        foreach ($list as $domain) {
            if ($host === $domain || substr($host, -strlen('.' . $domain)) === '.' . $domain) {
                return $network;
            }
        }
    }
    return null;
}

/** true se un core/social-links sta dentro un altro blocco. */
function oc8940_has_nested_social_links(array $blocks, bool $nested = false): bool
{
    foreach ($blocks as $block) {
        if ($nested && $block['blockName'] === 'core/social-links') {
            return true;
        }
        if (!empty($block['innerBlocks']) && oc8940_has_nested_social_links($block['innerBlocks'], true)) {
            return true;
        }
    }
    return false;
}

/** Il blocco core/social-links con i soli link indicati. */
function oc8940_social_links_block_with(array $block, array $kept): array
{
    $open = $block['innerContent'][0];
    $close = end($block['innerContent']);
    $inner_content = [$open];
    foreach (array_values($kept) as $i => $unused) {
        if ($i > 0) {
            $inner_content[] = "\n\n";
        }
        $inner_content[] = null;
    }
    $inner_content[] = $close;
    $block['innerBlocks'] = array_values($kept);
    $block['innerContent'] = $inner_content;
    return $block;
}

/**
 * Cosa cambierebbe in una guida: contenuto nuovo, meta nuovi, correzioni, link rimasti.
 *
 * @return array{changed: bool, content: string, meta: array, delete_site: bool, corrections: array, to_check: bool, notes: array}
 */
function oc8940_plan_guide(int $post_id): array
{
    $networks = wm_pnfc_guide_social_networks();
    $content = get_post_field('post_content', $post_id, 'raw');
    $blocks = parse_blocks($content);
    $plan = ['changed' => false, 'content' => $content, 'meta' => [], 'delete_site' => false, 'corrections' => [], 'to_check' => false, 'notes' => []];

    if (oc8940_has_nested_social_links($blocks)) {
        $plan['to_check'] = true;
        $plan['notes'][] = 'blocco social annidato: guida non toccata';
        return $plan;
    }

    $found = [];
    $new_blocks = [];
    $skip_next_whitespace = false;
    foreach ($blocks as $block) {
        if ($skip_next_whitespace && $block['blockName'] === null && trim($block['innerHTML']) === '') {
            $skip_next_whitespace = false;
            continue;
        }
        $skip_next_whitespace = false;
        if ($block['blockName'] !== 'core/social-links') {
            $new_blocks[] = $block;
            continue;
        }
        $kept = [];
        foreach ($block['innerBlocks'] as $link) {
            $service = $link['attrs']['service'] ?? '';
            $raw = $link['attrs']['url'] ?? '';
            $url = isset($networks[$service]) ? wm_pnfc_normalize_social_url($raw, $service) : null;
            if ($url === null) {
                $kept[] = $link;
                $plan['to_check'] = true;
                $plan['notes'][] = "link non migrato [{$service}] «{$raw}»";
                continue;
            }
            if ($url !== $raw) {
                $plan['corrections'][] = "[{$service}] «{$raw}» → {$url}";
            }
            $found[$service][] = $url;
        }
        if (empty($kept)) {
            // Il blocco sparisce, con la riga vuota che lo seguiva.
            $skip_next_whitespace = true;
            if (!empty($new_blocks) && end($new_blocks)['blockName'] === null && trim(end($new_blocks)['innerHTML']) === '') {
                array_pop($new_blocks);
                $skip_next_whitespace = false;
            }
            continue;
        }
        $new_blocks[] = count($kept) === count($block['innerBlocks']) ? $block : oc8940_social_links_block_with($block, $kept);
    }

    // Social scritto nel campo «Url:»
    $site = (string) get_post_meta($post_id, '_guide_sito', true);
    if ($site !== '' && ($network = oc8940_network_for_url(preg_match('#^https?://#i', $site) ? $site : 'https://' . $site)) !== null) {
        $url = wm_pnfc_normalize_social_url($site, $network);
        if ($url !== null) {
            $found[$network][] = $url;
            $plan['delete_site'] = true;
            $plan['corrections'][] = "campo Url «{$site}» → {$network}: {$url}";
        }
    }

    foreach ($found as $network => $urls) {
        $meta_key = $networks[$network]['meta_key'];
        $current = (string) get_post_meta($post_id, $meta_key, true);
        $existing = wm_pnfc_parse_social_urls($current, $network);
        $valid = $existing['valid'];
        foreach ($urls as $url) {
            if (!in_array($url, $valid, true)) {
                $valid[] = $url;
            }
        }
        $value = implode("\n", array_merge($valid, $existing['invalid']));
        if ($value !== $current) {
            $plan['meta'][$meta_key] = $value;
        }
    }

    $plan['content'] = serialize_blocks($new_blocks);
    $plan['changed'] = $plan['content'] !== $content || !empty($plan['meta']) || $plan['delete_site'];
    return $plan;
}

/** Legge il JSON del prima; un file esistente ma illeggibile ferma tutto invece di essere riscritto. */
function oc8940_read_before(string $file): array
{
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        throw new \RuntimeException("JSON del prima illeggibile: {$file}. Mi fermo per non perderlo.");
    }
    return $data;
}

/** Scrive il JSON del prima; se non ci riesce ferma tutto prima di toccare la guida. */
function oc8940_write_before(string $file, array $before): void
{
    $json = wp_json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || @file_put_contents($file, $json) === false) {
        throw new \RuntimeException("Non riesco a scrivere il JSON del prima: {$file}. Nessuna guida toccata.");
    }
}

/** Meta toccati dalla migrazione, per il salvataggio del prima. */
function oc8940_meta_keys(): array
{
    return array_merge(['_guide_sito'], array_values(wp_list_pluck(wm_pnfc_guide_social_networks(), 'meta_key')));
}

/** Guide su cui lavora la migrazione: tutte tranne cestino e bozze automatiche, tutte le lingue. */
function oc8940_guide_ids(): array
{
    return get_posts([
        'post_type'        => 'guide',
        'post_status'      => ['publish', 'draft', 'pending', 'private', 'future'],
        'numberposts'      => -1,
        'fields'           => 'ids',
        'orderby'          => 'ID',
        'order'            => 'ASC',
        'suppress_filters' => true,
    ]);
}

/**
 * Esegue la migrazione (dry-run, apply) o il ripristino (restore) sulle guide indicate.
 *
 * @return array{changed: int, to_check: int[], corrections: string[]}
 */
function oc8940_run(array $ids, string $mode, string $backup_dir): array
{
    $before_file = trailingslashit($backup_dir) . OC8940_BEFORE_FILE;
    $before = oc8940_read_before($before_file);
    $report = ['changed' => 0, 'to_check' => [], 'corrections' => []];

    if ($mode === 'restore') {
        foreach ($ids as $id) {
            if (!isset($before[$id])) {
                continue;
            }
            foreach ($before[$id]['meta'] as $key => $value) {
                if ($value === null) {
                    delete_post_meta($id, $key);
                } else {
                    update_post_meta($id, $key, wp_slash($value));
                }
            }
            wp_update_post(wp_slash(['ID' => $id, 'post_content' => $before[$id]['post_content']]));
            $report['changed']++;
            WP_CLI::log("ripristinata {$id} " . get_the_title($id));
        }
        return $report;
    }

    foreach ($ids as $id) {
        $plan = oc8940_plan_guide($id);
        if ($plan['to_check']) {
            $report['to_check'][] = $id;
        }
        if (!$plan['changed']) {
            continue;
        }
        $report['changed']++;
        WP_CLI::log("{$id} " . get_the_title($id) . ': ' . implode(', ', array_map(
            fn($k, $v) => $k . ' = ' . str_replace("\n", ' + ', $v),
            array_keys($plan['meta']),
            $plan['meta']
        )) . ($plan['delete_site'] ? ', _guide_sito svuotato' : ''));
        foreach ($plan['corrections'] as $c) {
            WP_CLI::log("   correzione: {$c}");
            $report['corrections'][] = "{$id}: {$c}";
        }
        foreach ($plan['notes'] as $n) {
            WP_CLI::log("   da controllare: {$n}");
        }
        if ($mode !== 'apply') {
            continue;
        }
        if (!isset($before[$id])) {
            $meta = [];
            foreach (oc8940_meta_keys() as $key) {
                $meta[$key] = metadata_exists('post', $id, $key) ? get_post_meta($id, $key, true) : null;
            }
            $before[$id] = ['post_content' => get_post_field('post_content', $id, 'raw'), 'meta' => $meta];
            oc8940_write_before($before_file, $before);
        }
        // Prima i meta, poi il contenuto: se lo script si interrompe in mezzo, il blocco social è
        // ancora nel testo e un nuovo lancio completa il lavoro.
        foreach ($plan['meta'] as $key => $value) {
            update_post_meta($id, $key, wp_slash($value));
        }
        if ($plan['delete_site']) {
            delete_post_meta($id, '_guide_sito');
        }
        if ($plan['content'] !== get_post_field('post_content', $id, 'raw')) {
            $result = wp_update_post(wp_slash(['ID' => $id, 'post_content' => $plan['content']]), true);
            if (is_wp_error($result)) {
                throw new \RuntimeException("{$id}: " . $result->get_error_message());
            }
        }
    }
    return $report;
}
