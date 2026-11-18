<?php
/**
 * oc:8940 — controlli dei campi social del meta box e del salvataggio.
 * Uso, solo nella copia locale: wp eval-file check-contact-fields.php
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}
require __DIR__ . '/check-helpers.php';

// wm_pnfc_normalize_social_url — casi veri del 18/11/2026
$cases = [
    ['www.instagram.com/boschiromagnoli', 'instagram', 'https://www.instagram.com/boschiromagnoli'],
    ['https://www.instagram.com/bagnodiforesta ', 'instagram', 'https://www.instagram.com/bagnodiforesta'],
    ['wilder_trekk', 'instagram', 'https://www.instagram.com/wilder_trekk/'],
    ['@wilder_trekk', 'instagram', 'https://www.instagram.com/wilder_trekk/'],
    ['romagna.selvatica', 'instagram', 'https://www.instagram.com/romagna.selvatica/'],
    ['instagram.com/altrevie', 'instagram', 'https://instagram.com/altrevie'],
    ['https://www.facebook.com/ltrevie/?locale=it_IT', 'facebook', 'https://www.facebook.com/ltrevie/?locale=it_IT'],
    ['nicola-busi', 'linkedin', 'https://www.linkedin.com/in/nicola-busi/'],
    ['@canale', 'youtube', 'https://www.youtube.com/@canale/'],
    ['due parole', 'instagram', null],
    ['', 'instagram', null],
];
foreach ($cases as [$line, $network, $expected]) {
    $actual = check_call(fn() => wm_pnfc_normalize_social_url($line, $network), "normalize «{$line}»");
    assert_same($expected, $actual, "normalize «{$line}» ({$network})");
}

// wm_pnfc_parse_social_urls
assert_same(
    ['valid' => ['https://www.instagram.com/altrevie/', 'https://www.instagram.com/eticaselvatica/'], 'invalid' => []],
    check_call(fn() => wm_pnfc_parse_social_urls("https://www.instagram.com/altrevie/\n\n https://www.instagram.com/eticaselvatica/ \n", 'instagram'), 'parse'),
    'parse: due profili, righe vuote e spazi'
);
assert_same(
    ['valid' => ['https://x.com/a'], 'invalid' => ['due parole']],
    check_call(fn() => wm_pnfc_parse_social_urls("https://x.com/a\nhttps://x.com/a\ndue parole", 'x'), 'parse'),
    'parse: doppione tolto, riga non valida tenuta a parte'
);

// wm_pnfc_guide_social_networks
$networks = check_call(fn() => wm_pnfc_guide_social_networks(), 'networks');
assert_same(['facebook', 'instagram', 'linkedin', 'x', 'youtube', 'tiktok'], is_array($networks) ? array_keys($networks) : null, 'ordine dei social');
assert_same('_guide_x', $networks['x']['meta_key'] ?? null, 'meta key di X');

// Salvataggio dal meta box
$admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
wp_set_current_user((int) $admins[0]);
$guide = check_create_guide();
update_post_meta($guide, '_guide_telefono', '333 0000000');

$save = function (array $fields) use ($guide) {
    $_POST = array_merge(['guide_contact_info_nonce' => wp_create_nonce('save_guide_contact_info')], $fields);
    save_guide_contact_info($guide);
    $_POST = [];
};

check_call(fn() => $save(['guide_social_instagram' => "https://www.instagram.com/altrevie/\nwww.instagram.com/eticaselvatica"]), 'salvataggio due righe');
assert_same("https://www.instagram.com/altrevie/\nhttps://www.instagram.com/eticaselvatica", get_post_meta($guide, '_guide_instagram', true), 'salva due profili, il secondo corretto');
assert_same('333 0000000', get_post_meta($guide, '_guide_telefono', true), 'campo telefono assente dal form: invariato');

check_call(fn() => $save([]), 'salvataggio senza campi social');
assert_same("https://www.instagram.com/altrevie/\nhttps://www.instagram.com/eticaselvatica", get_post_meta($guide, '_guide_instagram', true), 'campo social assente dal form: invariato');

check_call(fn() => $save(['guide_social_instagram' => "https://www.instagram.com/altrevie/\ndue parole"]), 'salvataggio con riga non valida');
assert_same("https://www.instagram.com/altrevie/\ndue parole", get_post_meta($guide, '_guide_instagram', true), 'riga non valida tenuta nel meta');

check_call(fn() => $save(['guide_social_facebook' => 'https://www.facebook.com/Rifugio-Citt%C3%A0-123/', 'guide_sito' => 'https://www.esempio.it/citt%C3%A0']), 'salvataggio con caratteri codificati');
assert_same('https://www.facebook.com/Rifugio-Citt%C3%A0-123/', get_post_meta($guide, '_guide_facebook', true), 'social con %XX conservato');
assert_same('https://www.esempio.it/citt%C3%A0', get_post_meta($guide, '_guide_sito', true), 'sito con %XX conservato');

check_call(fn() => $save(['guide_social_instagram' => '', 'guide_telefono' => '']), 'salvataggio campi vuoti');
assert_same('', get_post_meta($guide, '_guide_instagram', true), 'campo social vuoto: meta cancellato');
assert_same(false, metadata_exists('post', $guide, '_guide_telefono'), 'campo telefono vuoto: meta cancellato');

check_cleanup();
check_finish();
