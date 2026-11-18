<?php
/**
 * oc:8940 — traduzioni inglesi delle label nuove del child theme, in WPML String Translation.
 *
 * Uso: wp eval-file apply-translations.php [dry-run|apply] --user=<amministratore>
 * Senza modalità: dry-run. Una traduzione già presente e diversa non viene sovrascritta: viene
 * segnalata. Rilanciato dopo un apply, non cambia nulla.
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}

$mode = $args[0] ?? 'dry-run';
if (!in_array($mode, ['dry-run', 'apply'], true)) {
    WP_CLI::error('Modalità ammesse: dry-run, apply.');
}
if (!function_exists('icl_register_string') || !function_exists('icl_add_string_translation')) {
    WP_CLI::error('WPML String Translation non attivo.');
}

const OC8940_DOMAIN = 'wm-pnfc-child';
$translations = [
    'Contatti'                   => 'Contacts',
    'In breve'                   => 'At a glance',
    'Telefono'                   => 'Phone',
    'Email'                      => 'Email',
    'Sito'                       => 'Website',
    'Social'                     => 'Social',
    'Attività proposte'          => 'Proposed activities',
    'Attività proposta'          => 'Proposed activity',
    'Lingue parlate'             => 'Spoken languages',
    'Aree operative'             => 'Operating areas',
    'Se la guida ha più profili su questo social, inserisci un indirizzo per riga.' => 'If the guide has more than one profile on this social network, enter one address per line.',
    'Indirizzo non riconosciuto:' => 'Address not recognised:',
    '%1$d caratteri · consigliati circa %2$d' => '%1$d characters · about %2$d recommended',
];

global $wpdb;
$changed = 0;
$language_changed = [];
foreach ($translations as $it => $en) {
    $name = md5($it);
    $string_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}icl_strings WHERE context = %s AND name = %s",
        OC8940_DOMAIN,
        $name
    ));
    $current = $string_id ? $wpdb->get_var($wpdb->prepare(
        "SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE string_id = %d AND language = 'en'",
        $string_id
    )) : null;
    // Le stringhe del tema registrate prima di oc:8940 hanno come lingua di origine l'inglese,
    // anche se il testo è italiano: una traduzione inglese di una stringa «inglese» WPML non la
    // usa. Si corregge la lingua di origine delle sole stringhe di questo elenco.
    $language = $string_id ? $wpdb->get_var($wpdb->prepare("SELECT language FROM {$wpdb->prefix}icl_strings WHERE id = %d", $string_id)) : 'it';
    if ($current === $en && $language === 'it') {
        WP_CLI::log("«{$it}»: già «{$en}»");
        continue;
    }
    if ($current === $en) {
        $changed++;
        WP_CLI::log("«{$it}»: già «{$en}», lingua di origine da «{$language}» a «it»");
        if ($mode === 'apply') {
            $wpdb->update("{$wpdb->prefix}icl_strings", ['language' => 'it'], ['id' => $string_id]);
            icl_update_string_status($string_id);
            $language_changed[] = $string_id;
        }
        continue;
    }
    if ($current !== null && $current !== '') {
        WP_CLI::warning("«{$it}»: traduzione esistente «{$current}», non la sovrascrivo");
        continue;
    }
    $changed++;
    WP_CLI::log("«{$it}» → «{$en}»" . ($string_id ? '' : ' (stringa da registrare)'));
    if ($mode !== 'apply') {
        continue;
    }
    if (!$string_id) {
        $string_id = (int) icl_register_string(OC8940_DOMAIN, $name, $it, false, 'it');
    } elseif ($language !== 'it') {
        $wpdb->update("{$wpdb->prefix}icl_strings", ['language' => 'it'], ['id' => $string_id]);
        $language_changed[] = $string_id;
    }
    icl_add_string_translation($string_id, 'en', $en, ICL_TM_COMPLETE);
    icl_update_string_status($string_id);
}

if ($mode === 'apply') {
    // Rigenera i file di traduzione che WPML scrive in wp-content/languages/wpml/ per il dominio
    // del tema: senza, la pagina continua a usare quelli generati prima.
    if (!empty($language_changed)) {
        do_action('wpml_st_language_of_strings_changed', $language_changed);
    }
    do_action('wpml_st_refresh_domain', OC8940_DOMAIN);
    WP_CLI::success("{$changed} traduzioni scritte");
} else {
    WP_CLI::success("dry-run: {$changed} traduzioni da scrivere");
}
