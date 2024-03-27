<?php
if (!is_admin()) {
    add_shortcode('wm_grid_track', 'wm_grid_track');
}

function wm_grid_track($atts)
{
    if (defined('ICL_LANGUAGE_CODE')) {
        $language = ICL_LANGUAGE_CODE;
    } else {
        $language = 'it';
    }
    extract(shortcode_atts(array(
        'ids' => '',
        'quantity' => -1,
        'random' => 'false'
    ), $atts));

    $ids_array = !empty($ids) ? explode(',', $ids) : [];
    // echo '<pre>';
    // print_r($ids);
    // echo '</pre>';
    $tracks = [];

    foreach ($ids_array as $id) {
        $track_url = "https://geohub.webmapp.it/api/ec/track/{$id}";
        $response = wp_remote_get($track_url);

        if (is_wp_error($response)) {
            continue;
        }
        $track_data = json_decode(wp_remote_retrieve_body($response), true);
        if (!empty($track_data)) {
            $tracks[] = $track_data;
        }
        if ('true' === $random) {
            shuffle($tracks);
        }
        if ($quantity > 0 && count($tracks) > $quantity) {
            $tracks = array_slice($tracks, 0, $quantity);
        }
    }
    ob_start();
?>

    <div class="wm_tracks_grid">
        <?php foreach ($tracks as $track) : ?>
            <div class="wm_grid_track_item">
                <?php
                $name = $track['properties']['name'][$language] ?? '';
                $activity = $track['properties']['taxonomy']['activity'][0]['name'][$language] ?? '';
                $feature_image_url = $track['properties']['feature_image']['thumbnail'] ?? '/assets/images/background.jpg';
                $name_url = wm_custom_slugify($name);
                $language_prefix = $language === 'en' ? '/en' : '';
                $track_page_url = "{$language_prefix}/track/{$name_url}/";
                $icon = '';
                if ($activity === 'Trekking') {
                    $icon = '<span class="fas fa-hiking"></span>';
                } elseif ($activity === 'MTB') {
                    $icon = '<span class="fas fa-biking"></span>';
                }
                ?>
                <a href="<?= esc_url($track_page_url); ?>">
                    <div class="wm_grid_track_image" style="background-image: url('<?= esc_url($feature_image_url); ?>');">
                        <div class="wm_grid_track_info">
                            <?php if ($activity) : ?>
                                <p><?= $icon; ?> <span><?= esc_html($activity); ?></span></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($name) : ?>
                        <div class="wm_grid_track_name"><?= esc_html($name); ?></div>
                    <?php endif; ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>


<?php

    return ob_get_clean();
}
?>