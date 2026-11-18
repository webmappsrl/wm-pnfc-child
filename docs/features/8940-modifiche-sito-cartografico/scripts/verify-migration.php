<?php
/**
 * oc:8940 — verifica, guida per guida, che la migrazione dei social non abbia perso dati.
 * Confronta lo stato attuale con il JSON del prima scritto da migrate-social-links.php, con
 * controlli indipendenti dalla logica della migrazione:
 *  - ogni link del vecchio blocco core/social-links è nei meta (corretto) o è rimasto nel testo;
 *  - un social scritto nel campo «Url:» è finito nei meta;
 *  - tolti i blocchi social, il testo della guida è identico a prima;
 *  - i meta social che c'erano non hanno perso indirizzi; il campo «Url:» cambia solo se era un social.
 *
 * Uso: wp eval-file verify-migration.php <cartella-backup>
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}
require __DIR__ . '/social-migration-lib.php';

$backup_dir = $args[0] ?? '';
$before_file = trailingslashit($backup_dir) . OC8940_BEFORE_FILE;
if (!file_exists($before_file)) {
    WP_CLI::error("JSON del prima non trovato: {$before_file}");
}
$before = json_decode(file_get_contents($before_file), true);
if (!is_array($before) || empty($before)) {
    WP_CLI::error('JSON del prima vuoto o non leggibile.');
}

$networks = wm_pnfc_guide_social_networks();
$strip_social = function (string $content): string {
    $content = preg_replace('#<!-- wp:social-links(?:\s[^>]*)? -->.*?<!-- /wp:social-links -->#s', '', $content);
    return trim(preg_replace("/\n{3,}/", "\n\n", $content));
};
$valid_urls = function (?string $value, string $network): array {
    return wm_pnfc_parse_social_urls((string) $value, $network)['valid'];
};

$failures = 0;
$checked_links = 0;
foreach ($before as $id => $old) {
    $id = (int) $id;
    $problems = [];
    $new_content = get_post_field('post_content', $id, 'raw');
    $new_meta = [];
    foreach ($networks as $network => $social) {
        $new_meta[$network] = $valid_urls(get_post_meta($id, $social['meta_key'], true), $network);
    }

    // 1. Link del vecchio blocco social
    foreach (parse_blocks($old['post_content']) as $block) {
        if ($block['blockName'] !== 'core/social-links') {
            continue;
        }
        foreach ($block['innerBlocks'] as $link) {
            $service = $link['attrs']['service'] ?? '';
            $raw = $link['attrs']['url'] ?? '';
            $checked_links++;
            $url = isset($networks[$service]) ? wm_pnfc_normalize_social_url($raw, $service) : null;
            $in_meta = $url !== null && in_array($url, $new_meta[$service], true);
            $in_content = $raw !== '' && strpos($new_content, '"url":"' . $raw . '"') !== false;
            if (!$in_meta && !$in_content) {
                $problems[] = "link [{$service}] «{$raw}» né nei meta né nel testo";
            }
        }
    }

    // 2. Campo «Url:»
    $old_site = (string) ($old['meta']['_guide_sito'] ?? '');
    $new_site = (string) get_post_meta($id, '_guide_sito', true);
    if ($old_site !== $new_site) {
        $network = oc8940_network_for_url(preg_match('#^https?://#i', $old_site) ? $old_site : 'https://' . $old_site);
        $url = $network ? wm_pnfc_normalize_social_url($old_site, $network) : null;
        if ($url === null || !in_array($url, $new_meta[$network], true)) {
            $problems[] = "campo Url «{$old_site}» cambiato e non ritrovato nei meta";
        }
    }

    // 3. Testo della guida, tolti i blocchi social
    if ($strip_social($old['post_content']) !== $strip_social($new_content)) {
        $problems[] = 'il testo della guida (tolti i blocchi social) è cambiato';
    }

    // 4. Meta social già presenti
    foreach ($networks as $network => $social) {
        foreach ($valid_urls($old['meta'][$social['meta_key']] ?? '', $network) as $url) {
            if (!in_array($url, $new_meta[$network], true)) {
                $problems[] = "{$social['meta_key']}: perso «{$url}»";
            }
        }
        $old_invalid = wm_pnfc_parse_social_urls((string) ($old['meta'][$social['meta_key']] ?? ''), $network)['invalid'];
        $new_invalid = wm_pnfc_parse_social_urls((string) get_post_meta($id, $social['meta_key'], true), $network)['invalid'];
        foreach (array_diff($old_invalid, $new_invalid) as $line) {
            $problems[] = "{$social['meta_key']}: persa la riga «{$line}»";
        }
    }

    if ($problems) {
        $failures++;
        WP_CLI::log("FAIL {$id} " . get_the_title($id));
        foreach ($problems as $p) {
            WP_CLI::log("     {$p}");
        }
    } else {
        WP_CLI::log("OK   {$id} " . get_the_title($id));
    }
}

if ($failures) {
    WP_CLI::error("{$failures} guide con dati non ritrovati su " . count($before));
}
WP_CLI::success(count($before) . " guide verificate, {$checked_links} link controllati: nessun dato perso");
