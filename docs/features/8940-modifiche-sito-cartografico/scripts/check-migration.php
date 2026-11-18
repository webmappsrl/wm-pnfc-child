<?php
/**
 * oc:8940 — controlli della migrazione dei social dal blocco core/social-links ai meta.
 * Uso, solo nella copia locale: wp eval-file check-migration.php --user=<amministratore>
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}
require __DIR__ . '/check-helpers.php';
require __DIR__ . '/social-migration-lib.php';

$backup = sys_get_temp_dir() . '/oc8940-check-' . wp_generate_password(6, false);
wp_mkdir_p($backup);

$p1 = "<!-- wp:paragraph -->\n<p>Primo paragrafo &amp; \"citazioni\"</p>\n<!-- /wp:paragraph -->";
$p2 = "<!-- wp:paragraph -->\n<p>Secondo paragrafo</p>\n<!-- /wp:paragraph -->";
$links = function (array $pairs) {
    $inner = [];
    foreach ($pairs as [$service, $url]) {
        $inner[] = '<!-- wp:social-link {"url":"' . $url . '","service":"' . $service . '"} /-->';
    }
    return "<!-- wp:social-links -->\n<ul class=\"wp-block-social-links\">" . implode("\n\n", $inner) . "</ul>\n<!-- /wp:social-links -->";
};

// 1. Instagram e Facebook tra due paragrafi
$g1 = check_create_guide(['post_content' => $p1 . "\n\n" . $links([['instagram', 'https://www.instagram.com/uno/'], ['facebook', 'https://www.facebook.com/uno']]) . "\n\n" . $p2]);
// 2. Due profili Instagram, uno scritto male
$g2 = check_create_guide(['post_content' => $links([['instagram', 'https://www.instagram.com/altrevie/'], ['instagram', 'eticaselvatica']])]);
// 3. Servizio non previsto e link non correggibile
$g3 = check_create_guide(['post_content' => $links([['instagram', 'https://www.instagram.com/tre/'], ['mastodon', 'https://mastodon.social/@tre'], ['facebook', 'due parole']])]);
// 4. Meta già compilato con un altro indirizzo
$g4 = check_create_guide(['post_content' => $links([['instagram', 'https://www.instagram.com/quattro/']])]);
update_post_meta($g4, '_guide_instagram', 'https://www.instagram.com/gia-presente/');
// 5. Instagram nel campo «Url:» e nel blocco (caso 4925)
$g5 = check_create_guide(['post_content' => $links([['instagram', 'www.instagram.com/cinque']])]);
update_post_meta($g5, '_guide_sito', 'www.instagram.com/cinque');
// 6. Sito vero nel campo «Url:»: non si tocca
$g6 = check_create_guide(['post_content' => $p1]);
update_post_meta($g6, '_guide_sito', 'www.orostoscana.it');

$ids = [$g1, $g2, $g3, $g4, $g5, $g6];
$snapshot = function () use ($ids) {
    $s = [];
    foreach ($ids as $id) {
        $s[$id] = [get_post_field('post_content', $id, 'raw'), get_post_meta($id)];
    }
    return $s;
};
$original = $snapshot();

// JSON del prima che non si riesce a scrivere (al suo posto c'è una cartella, errore anche per
// root): la migrazione si ferma prima di toccare le guide
$unwritable = sys_get_temp_dir() . '/oc8940-unwritable-' . wp_generate_password(6, false);
wp_mkdir_p($unwritable . '/' . OC8940_BEFORE_FILE);
$stopped = false;
try {
    oc8940_run($ids, 'apply', $unwritable);
} catch (\RuntimeException $e) {
    $stopped = true;
}
assert_true($stopped, 'JSON del prima non scrivibile: migrazione fermata');
assert_same($original, $snapshot(), 'JSON del prima non scrivibile: nessuna guida toccata');

// JSON del prima illeggibile: la migrazione si ferma invece di riscriverlo
$broken = sys_get_temp_dir() . '/oc8940-broken-' . wp_generate_password(6, false);
wp_mkdir_p($broken);
file_put_contents($broken . '/' . OC8940_BEFORE_FILE, '{non json');
$stopped = false;
try {
    oc8940_run($ids, 'apply', $broken);
} catch (\RuntimeException $e) {
    $stopped = true;
}
assert_true($stopped, 'JSON del prima illeggibile: migrazione fermata');
assert_same('{non json', file_get_contents($broken . '/' . OC8940_BEFORE_FILE), 'JSON del prima illeggibile: file non riscritto');

$report = check_call(fn() => oc8940_run($ids, 'apply', $backup), 'apply');

assert_same("https://www.instagram.com/uno/", get_post_meta($g1, '_guide_instagram', true), '1: Instagram nel meta');
assert_same("https://www.facebook.com/uno", get_post_meta($g1, '_guide_facebook', true), '1: Facebook nel meta');
assert_same($p1 . "\n\n" . $p2, get_post_field('post_content', $g1, 'raw'), '1: blocco tolto, paragrafi identici');
assert_same("https://www.instagram.com/altrevie/\nhttps://www.instagram.com/eticaselvatica/", get_post_meta($g2, '_guide_instagram', true), '2: due profili, il secondo corretto');
assert_same('https://www.instagram.com/tre/', get_post_meta($g3, '_guide_instagram', true), '3: Instagram migrato');
$c3 = get_post_field('post_content', $g3, 'raw');
assert_true(strpos($c3, 'mastodon') !== false && strpos($c3, 'due parole') !== false && strpos($c3, 'instagram.com/tre') === false, '3: restano solo i link non migrati');
assert_true(in_array($g3, $report['to_check'] ?? [], true), '3: guida da controllare');
assert_same("https://www.instagram.com/gia-presente/\nhttps://www.instagram.com/quattro/", get_post_meta($g4, '_guide_instagram', true), '4: meta esistente tenuto, indirizzo aggiunto');
assert_same('https://www.instagram.com/cinque', get_post_meta($g5, '_guide_instagram', true), '5: un solo indirizzo dal campo Url e dal blocco');
assert_same(false, metadata_exists('post', $g5, '_guide_sito'), '5: campo Url svuotato');
assert_same('www.orostoscana.it', get_post_meta($g6, '_guide_sito', true), '6: sito vero non toccato');
assert_true(!empty($report['corrections']), 'correzioni elencate nel report');

$second = check_call(fn() => oc8940_run($ids, 'apply', $backup), 'secondo apply');
assert_same(0, $second['changed'] ?? null, 'secondo apply: nessuna guida modificata');

// Un editor rimasto aperto rimette il blocco
wp_update_post(wp_slash(['ID' => $g1, 'post_content' => $original[$g1][0]]));
$third = check_call(fn() => oc8940_run($ids, 'apply', $backup), 'apply dopo blocco rimesso');
assert_same(1, $third['changed'] ?? null, 'blocco rimesso: una guida modificata');
assert_same("https://www.instagram.com/uno/", get_post_meta($g1, '_guide_instagram', true), 'blocco rimesso: nessun doppione nel meta');

// Ripristino
check_call(fn() => oc8940_run($ids, 'restore', $backup), 'restore');
$restored = $snapshot();
foreach ($ids as $i => $id) {
    assert_same($original[$id][0], $restored[$id][0], 'restore: contenuto ' . ($i + 1));
    $keys = array_merge(['_guide_sito'], wp_list_pluck(wm_pnfc_guide_social_networks(), 'meta_key'));
    $pick = fn($m) => array_intersect_key($m, array_flip($keys));
    assert_same($pick($original[$id][1]), $pick($restored[$id][1]), 'restore: meta ' . ($i + 1));
}

check_cleanup();
check_finish();
