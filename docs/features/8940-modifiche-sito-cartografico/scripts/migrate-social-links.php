<?php
/**
 * oc:8940 — sposta i social delle guide dal blocco Gutenberg core/social-links ai meta del meta box
 * «Informazioni di Contatto», correggendo gli indirizzi scritti male.
 *
 * Uso: wp eval-file migrate-social-links.php <cartella-backup> [dry-run|apply|restore] --user=<amministratore>
 * Senza modalità: dry-run. In apply, prima di toccare una guida ne salva contenuto e meta in
 * <cartella-backup>/social-migration-before.json; restore rimette quei valori sulle sole guide
 * toccate. Rilanciato, non cambia le guide già a posto.
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}
require __DIR__ . '/social-migration-lib.php';

$backup_dir = $args[0] ?? '';
$mode = $args[1] ?? 'dry-run';
if ($backup_dir === '' || !is_dir($backup_dir)) {
    WP_CLI::error('Indica la cartella del backup del lavoro come primo argomento.');
}
if (!in_array($mode, ['dry-run', 'apply', 'restore'], true)) {
    WP_CLI::error('Modalità ammesse: dry-run, apply, restore.');
}
if ($mode !== 'dry-run' && !current_user_can('unfiltered_html')) {
    WP_CLI::error('In apply e restore serve --user=<amministratore>: senza, i filtri kses alterano il contenuto.');
}
if (!function_exists('wm_pnfc_normalize_social_url')) {
    WP_CLI::error('Funzioni del child theme non trovate: prima va rilasciato il codice di oc:8940.');
}

$ids = oc8940_guide_ids();
WP_CLI::log("Guide esaminate: " . count($ids) . " — modalità {$mode}");
try {
    $report = oc8940_run($ids, $mode, $backup_dir);
} catch (\RuntimeException $e) {
    WP_CLI::error($e->getMessage());
}

if (!empty($report['to_check'])) {
    WP_CLI::warning('Guide da controllare a mano: ' . implode(', ', $report['to_check']));
}
WP_CLI::success(($mode === 'dry-run' ? 'Da modificare: ' : 'Modificate: ') . $report['changed'] . ' guide, correzioni di indirizzo: ' . count($report['corrections']));
