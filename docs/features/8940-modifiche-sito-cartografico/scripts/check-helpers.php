<?php
/**
 * Funzioni comuni degli script di controllo di oc:8940.
 * Si eseguono solo con WP-CLI (`wp eval-file`), mai dal web.
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}

$GLOBALS['wm_check_failures'] = 0;

function assert_same($expected, $actual, string $message = ''): void
{
    if ($expected === $actual) {
        WP_CLI::log('OK   ' . $message);
        return;
    }
    $GLOBALS['wm_check_failures']++;
    WP_CLI::log('FAIL ' . $message . "\n     atteso:  " . var_export($expected, true) . "\n     ottenuto: " . var_export($actual, true));
}

function assert_true(bool $condition, string $message): void
{
    assert_same(true, $condition, $message);
}

function check_finish(): void
{
    if ($GLOBALS['wm_check_failures'] > 0) {
        WP_CLI::error($GLOBALS['wm_check_failures'] . ' controlli falliti');
    }
    WP_CLI::success('tutti i controlli sono passati');
}

/** Crea una guida di prova, cancellata a fine controllo con check_cleanup(). */
function check_create_guide(array $args = []): int
{
    $id = wp_insert_post(array_merge([
        'post_type'   => 'guide',
        'post_status' => 'draft',
        'post_title'  => 'oc8940 guida di prova',
    ], $args), true);
    if (is_wp_error($id)) {
        WP_CLI::error($id->get_error_message());
    }
    $GLOBALS['wm_check_posts'][] = $id;
    return $id;
}

function check_cleanup(): void
{
    foreach ($GLOBALS['wm_check_posts'] ?? [] as $id) {
        wp_delete_post($id, true);
    }
}

/** Esegue una funzione e trasforma gli errori fatali in FAIL. */
function check_call(callable $fn, string $message)
{
    try {
        return $fn();
    } catch (\Throwable $e) {
        $GLOBALS['wm_check_failures']++;
        WP_CLI::log('FAIL ' . $message . ': ' . $e->getMessage());
        return null;
    }
}
