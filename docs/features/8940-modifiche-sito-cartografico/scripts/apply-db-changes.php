<?php
/**
 * oc:8940 — modifiche al database del sito: card della griglia delle guide (us_grid_layout 4201),
 * label del filtro «Abilitazioni» → «Attività proposte» nella pagina «Guide» (4177) e placeholder
 * delle immagini di Impreza (opzione img_placeholder) con l'icona del Parco, che lo script importa
 * nella libreria media da assets/images/guide-placeholder.png del child theme.
 *
 * Uso: wp eval-file apply-db-changes.php <cartella-backup> [dry-run|apply] --user=<amministratore>
 * Senza modalità: dry-run. In apply, prima di scrivere salva il contenuto di prima in
 * <cartella-backup>/db-before-oc8940.json (mai sovrascritto, così un secondo apply non perde il
 * prima vero). Rilanciato dopo un apply, non cambia nulla.
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}

$backup_dir = $args[0] ?? '';
$mode = $args[1] ?? 'dry-run';
if ($backup_dir === '' || !is_dir($backup_dir)) {
    WP_CLI::error('Indica la cartella del backup del lavoro come primo argomento.');
}
if (!in_array($mode, ['dry-run', 'apply'], true)) {
    WP_CLI::error('Modalità ammesse: dry-run, apply.');
}
if ($mode === 'apply' && !current_user_can('unfiltered_html')) {
    WP_CLI::error('In apply serve --user=<amministratore>: senza, i filtri kses alterano il contenuto.');
}

const OC8940_GRID_LAYOUT_ID = 4201;
const OC8940_GUIDES_PAGE_ID = 4177;

/** Card della griglia: immagine 1:1 con placeholder, solo dentro l'oggetto post_image:1. */
function oc8940_new_grid_layout(string $content): string
{
    $start = strpos($content, '"post_image:1":{');
    $end = $start === false ? false : strpos($content, '},"', $start);
    if ($start === false || $end === false) {
        WP_CLI::error('Layout 4201: elemento post_image:1 non trovato.');
    }
    $segment = substr($content, $start, $end - $start);
    foreach (['"placeholder":', '"has_ratio":', '"ratio":'] as $key) {
        if (strpos($segment, $key) === false) {
            WP_CLI::error("Layout 4201: chiave {$key} non trovata in post_image:1, mi fermo.");
        }
    }
    $new = preg_replace(
        ['/"placeholder":\d/', '/"has_ratio":\d/', '/"ratio":"[^"]*"/'],
        ['"placeholder":1', '"has_ratio":1', '"ratio":"1x1"'],
        $segment,
        1
    );
    return substr($content, 0, $start) . $new . substr($content, $end);
}

/** Filtro della pagina Guide: label dell'elemento tax|guide_abilitazioni. */
function oc8940_new_guides_page(string $content): string
{
    return preg_replace_callback('/(\[us_grid_filter filter_items=")([^"]+)(")/', function ($m) {
        $items = json_decode(rawurldecode($m[2]), true);
        if (!is_array($items) || rawurlencode(wp_json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== $m[2]) {
            WP_CLI::error('Pagina 4177: filter_items non si rilegge in modo identico, mi fermo.');
        }
        foreach ($items as &$item) {
            if (($item['source'] ?? '') === 'tax|guide_abilitazioni') {
                $item['label'] = 'Attività proposte';
            }
        }
        unset($item);
        return $m[1] . rawurlencode(wp_json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . $m[3];
    }, $content, 1);
}

$changes = [
    OC8940_GRID_LAYOUT_ID => 'oc8940_new_grid_layout',
    OC8940_GUIDES_PAGE_ID => 'oc8940_new_guides_page',
];

$before_file = trailingslashit($backup_dir) . 'db-before-oc8940.json';
$before = [];
if (file_exists($before_file)) {
    $before = json_decode((string) file_get_contents($before_file), true);
    if (!is_array($before)) {
        WP_CLI::error("JSON del prima illeggibile: {$before_file}. Mi fermo per non perderlo.");
    }
}
$write_before = function (array $data) use ($before_file) {
    $json = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || @file_put_contents($before_file, $json) === false) {
        WP_CLI::error("Non riesco a scrivere il JSON del prima: {$before_file}. Nulla è stato modificato in questo passo.");
    }
};
$changed = 0;

foreach ($changes as $post_id => $transform) {
    $old = get_post_field('post_content', $post_id, 'raw');
    if ($old === '') {
        WP_CLI::error("Post {$post_id} non trovato o vuoto.");
    }
    $new = $transform($old);
    if ($new === $old) {
        WP_CLI::log("{$post_id} " . get_the_title($post_id) . ': nessuna modifica');
        continue;
    }
    $changed++;
    WP_CLI::log("{$post_id} " . get_the_title($post_id) . ': da modificare');
    foreach ([[$old, '  prima: '], [$new, '  dopo:  ']] as [$text, $prefix]) {
        if (preg_match('/"post_image:1":\{[^}]*\}/', $text, $m)) {
            WP_CLI::log($prefix . $m[0]);
        } elseif (preg_match('/filter_items="([^"]+)"/', $text, $m)) {
            WP_CLI::log($prefix . rawurldecode($m[1]));
        }
    }
    if ($mode !== 'apply') {
        continue;
    }
    if (!isset($before[$post_id])) {
        $before[$post_id] = $old;
        $write_before($before);
    }
    $result = wp_update_post(wp_slash(['ID' => $post_id, 'post_content' => $new]), true);
    if (is_wp_error($result)) {
        WP_CLI::error("{$post_id}: " . $result->get_error_message());
    }
    if (get_post_field('post_content', $post_id, 'raw') !== $new) {
        WP_CLI::error("{$post_id}: il contenuto salvato non coincide con quello atteso.");
    }
}

// Placeholder delle immagini di Impreza: l'icona del Parco su fondo verde (oc:8940). Impreza
// accetta l'ID di un allegato; sul sito il placeholder compare solo nelle guide (card 4201 e
// immagine della pagina). L'allegato si riconosce dal meta _oc8940_guide_placeholder.
$placeholder_id = (int) (get_posts([
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'numberposts'    => 1,
    'fields'         => 'ids',
    'meta_key'       => '_oc8940_guide_placeholder',
    'meta_value'     => '1',
])[0] ?? 0);
if (!$placeholder_id) {
    $source = get_stylesheet_directory() . '/assets/images/guide-placeholder.png';
    if (!file_exists($source)) {
        WP_CLI::error("Immagine del placeholder non trovata: {$source}");
    }
    $changed++;
    WP_CLI::log("placeholder: da importare nella libreria media da {$source}");
    if ($mode === 'apply') {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $tmp = wp_tempnam('guide-placeholder.png');
        copy($source, $tmp);
        $placeholder_id = media_handle_sideload(['name' => 'guide-placeholder.png', 'tmp_name' => $tmp], 0, 'Placeholder guide escursionistiche');
        if (is_wp_error($placeholder_id)) {
            WP_CLI::error('Import del placeholder: ' . $placeholder_id->get_error_message());
        }
        update_post_meta($placeholder_id, '_oc8940_guide_placeholder', '1');
        WP_CLI::log("placeholder importato: allegato {$placeholder_id}");
    } else {
        $placeholder_id = 'nuovo allegato';
    }
}
$options = get_option('usof_options_Impreza');
if (!is_array($options)) {
    WP_CLI::error('Opzioni di Impreza (usof_options_Impreza) non trovate.');
}
$old_placeholder = $options['img_placeholder'] ?? '';
if ((string) $old_placeholder === (string) $placeholder_id) {
    WP_CLI::log('img_placeholder: nessuna modifica');
} else {
    $changed++;
    WP_CLI::log("img_placeholder: da modificare\n  prima: {$old_placeholder}\n  dopo:  {$placeholder_id}");
    if ($mode === 'apply') {
        if (!array_key_exists('option:img_placeholder', $before)) {
            $before['option:img_placeholder'] = $old_placeholder;
            $write_before($before);
        }
        $options['img_placeholder'] = (string) $placeholder_id;
        update_option('usof_options_Impreza', $options);
    }
}

if ($mode === 'apply') {
    // Verifica del risultato, indipendente dalla trasformazione
    $layout = get_post_field('post_content', OC8940_GRID_LAYOUT_ID, 'raw');
    preg_match('/"post_image:1":\{[^}]*\}/', $layout, $m);
    foreach (['"placeholder":1', '"has_ratio":1', '"ratio":"1x1"'] as $expected) {
        if (strpos($m[0] ?? '', $expected) === false) {
            WP_CLI::error("Layout 4201: dopo l'apply manca {$expected}.");
        }
    }
    if (strpos(rawurldecode(get_post_field('post_content', OC8940_GUIDES_PAGE_ID, 'raw')), '"label":"Attività proposte"') === false) {
        WP_CLI::error('Pagina 4177: dopo l\'apply manca la label «Attività proposte».');
    }
    if ((string) (get_option('usof_options_Impreza')['img_placeholder'] ?? '') !== (string) $placeholder_id) {
        WP_CLI::error('img_placeholder: dopo l\'apply non è l\'allegato atteso.');
    }
    WP_CLI::success("{$changed} contenuti modificati; prima salvato in {$before_file}");
} else {
    WP_CLI::success("dry-run: {$changed} contenuti da modificare");
}
