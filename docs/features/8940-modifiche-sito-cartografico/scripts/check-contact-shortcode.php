<?php
/**
 * oc:8940 — controlli dell'output di [guide_contatti].
 * Uso, solo nella copia locale: wp eval-file check-contact-shortcode.php
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}
require __DIR__ . '/check-helpers.php';

$render = fn(int $id) => do_shortcode('[guide_contatti id="' . $id . '"]');
$position = fn(string $html, string $needle) => ($p = strpos($html, $needle)) === false ? -1 : $p;

// Guida completa
$full = check_create_guide(['post_excerpt' => "Riga 1\nRiga 2 <b>grassetto</b>"]);
update_post_meta($full, '_guide_telefono', '333 0000000');
update_post_meta($full, '_guide_instagram', "https://www.instagram.com/altrevie/\nhttps://www.instagram.com/eticaselvatica/\ndue parole");
update_post_meta($full, '_guide_x', 'https://x.com/prova');
update_post_meta($full, '_guide_facebook', 'https://www.facebook.com/profile.php?id=123');
$html = (string) check_call(fn() => $render($full), 'render guida completa');

assert_true(strpos($html, 'class="wm_info_details"') !== false, 'card .wm_info_details');
assert_true(strpos($html, '>' . __('Contatti', 'wm-pnfc-child') . '<') !== false, 'titolo «Contatti»');
$p_summary = $position($html, '>' . __('In breve', 'wm-pnfc-child') . '<');
$p_excerpt = $position($html, 'wm_guide_excerpt');
$p_contacts = $position($html, '>' . __('Contatti', 'wm-pnfc-child') . '<');
assert_true($p_summary >= 0 && $p_summary < $p_excerpt && $p_excerpt < $p_contacts, 'Riassunto in una card «In breve» prima della card «Contatti»');
assert_same(2, preg_match_all('/class="wm_info_details(?: [^"]*)?"/', $html), 'due card: «In breve» e «Contatti»');
$order = [
    $position($html, 'wm_guide_excerpt'),
    $position($html, 'fa-phone'),
    $position($html, 'fa-instagram'),
    $position($html, '<svg'),
];
assert_true(min($order) >= 0 && $order === array_values(array_unique($order)) && $order == array_values((function ($o) { sort($o); return $o; })($order)), 'ordine: Riassunto, telefono, Instagram, X');
assert_same(2, substr_count($html, 'fa-instagram'), 'un\'icona per ogni profilo Instagram valido');
assert_true(strpos($html, 'class="fab fa-instagram"') !== false, 'icone dei marchi con la classe fab di Font Awesome 5');
assert_true(strpos($html, 'due parole') === false, 'riga non riconosciuta non mostrata');
assert_true(strpos($html, 'Riga 1<br />') !== false, 'a capo del Riassunto mantenuti');
assert_true(strpos($html, '&lt;b&gt;grassetto') !== false, 'Riassunto passato da esc_html');
assert_true(strpos($html, 'rel="noopener noreferrer"') !== false, 'link social con rel');
assert_true(strpos($html, 'aria-label="Instagram: altrevie"') !== false, 'aria-label con il nome del profilo');
assert_true(strpos($html, 'aria-label="X: prova"') !== false, 'aria-label di X');
assert_true(strpos($html, '</i> ' . __('Social', 'wm-pnfc-child') . ':</span>') !== false, 'riga «Social» con la sua label');
assert_true(strpos($html, '</i> altrevie</a>') !== false && strpos($html, '</i> eticaselvatica</a>') !== false, 'nome di ogni profilo scritto accanto all\'icona');
assert_true(strpos($html, 'aria-label="Facebook"') !== false && strpos($html, '</i> Facebook</a>') !== false, 'indirizzo senza nome (profile.php): scritto «Facebook»');
assert_true(strpos($html, 'aria-hidden="true"') !== false, 'icone nascoste agli screen reader');

// Guida con il solo Riassunto: card «In breve», niente card «Contatti»
$only_excerpt = check_create_guide(['post_excerpt' => 'Solo riassunto']);
$html = (string) check_call(fn() => $render($only_excerpt), 'render guida con solo Riassunto');
assert_true(strpos($html, 'Solo riassunto') !== false && strpos($html, '>' . __('Contatti', 'wm-pnfc-child') . '<') === false, 'solo Riassunto: card «In breve» senza «Contatti»');

// Guida vuota
$empty = check_create_guide();
assert_same('', check_call(fn() => $render($empty), 'render guida vuota'), 'guida senza dati: nessun output');

// Guida con i soli termini di guide_lingue
$term = wp_insert_term('oc8940 lingua di prova', 'guide_lingue');
$only_terms = check_create_guide();
wp_set_object_terms($only_terms, [(int) $term['term_id']], 'guide_lingue');
$html = (string) check_call(fn() => $render($only_terms), 'render guida con soli termini');
assert_true(strpos($html, __('Lingue parlate', 'wm-pnfc-child')) !== false && strpos($html, 'fa-phone') === false, 'card con la sola riga «Lingue parlate»');
wp_delete_term((int) $term['term_id'], 'guide_lingue');

// Nome del profilo
assert_same('ltrevie', check_call(fn() => wm_pnfc_social_profile_name('https://www.facebook.com/ltrevie/?locale=it_IT'), 'profile name'), 'nome del profilo dall\'indirizzo');
assert_same('nicola-busi-72576b22a', check_call(fn() => wm_pnfc_social_profile_name('https://www.linkedin.com/in/nicola-busi-72576b22a/'), 'profile name'), 'nome del profilo LinkedIn');
assert_same('canale', check_call(fn() => wm_pnfc_social_profile_name('https://www.youtube.com/@canale/'), 'profile name'), 'nome del profilo senza @');
assert_same('', check_call(fn() => wm_pnfc_social_profile_name('https://www.facebook.com/profile.php?id=123'), 'profile name'), 'profile.php: nessun nome');

// Label della tassonomia
assert_same('Attività proposte', get_taxonomy('guide_abilitazioni')->labels->name, 'label della tassonomia guide_abilitazioni');

// Traduzione inglese senza dati: usa quelli della guida italiana
$it = check_create_guide(['post_excerpt' => 'Testo italiano']);
update_post_meta($it, '_guide_telefono', '333 1111111');
update_post_meta($it, '_guide_facebook', 'https://www.facebook.com/prova');
do_action('wpml_set_element_language_details', ['element_id' => $it, 'element_type' => 'post_guide', 'trid' => false, 'language_code' => 'it']);
$trid = apply_filters('wpml_element_trid', null, $it, 'post_guide');
$en = check_create_guide(['post_title' => 'oc8940 test guide']);
do_action('wpml_set_element_language_details', ['element_id' => $en, 'element_type' => 'post_guide', 'trid' => $trid, 'language_code' => 'en', 'source_language_code' => 'it']);
$html = (string) check_call(fn() => $render($en), 'render guida inglese');
assert_true(strpos($html, '333 1111111') !== false, 'inglese: telefono della guida italiana');
assert_true(strpos($html, 'Testo italiano') !== false, 'inglese: Riassunto della guida italiana');
assert_true(strpos($html, 'Facebook: prova') !== false, 'inglese: social della guida italiana');

// Galleria: senza foto lo slider di Impreza mostra 3 immagini qualsiasi della libreria media,
// quindi la pagina deve sapere se la guida ha una galleria vera.
$no_gallery = check_create_guide();
$with_gallery = check_create_guide();
$any_image = (int) (get_posts(['post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids'])[0] ?? 0);
update_post_meta($with_gallery, 'us_tile_additional_image', (string) $any_image);
$deleted_only = check_create_guide();
update_post_meta($deleted_only, 'us_tile_additional_image', '999999999');
assert_same(false, check_call(fn() => wm_pnfc_guide_has_gallery($no_gallery), 'has_gallery'), 'guida senza galleria');
assert_same(true, check_call(fn() => wm_pnfc_guide_has_gallery($with_gallery), 'has_gallery'), 'guida con galleria');
assert_same(false, check_call(fn() => wm_pnfc_guide_has_gallery($deleted_only), 'has_gallery'), 'galleria con sole immagini cancellate');

check_cleanup();
check_finish();
