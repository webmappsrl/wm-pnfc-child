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
        'layer_id' => '',
        'layer_ids' => '',
        'quantity' => -1,
        'random' => 'false'
    ), $atts));

    $tracks = [];
    if (!empty($layer_ids) && $quantity > 0) {
        $layer_ids_array = explode(',', $layer_ids);
        foreach ($layer_ids_array as $single_layer_id) {
            $layer_url = "https://geohub.webmapp.it/api/app/webapp/49/layer/{$single_layer_id}";
            $response = wp_remote_get($layer_url);

            if (is_wp_error($response)) continue;

            $layer_data = json_decode(wp_remote_retrieve_body($response), true);
            $layer_tracks = $layer_data['tracks'] ?? [];

            $tracks = array_merge($tracks, $layer_tracks);
        }
    } else {
        if (!empty($layer_id)) {
            $layer_url = "https://geohub.webmapp.it/api/app/webapp/49/layer/{$layer_id}";
            $response = wp_remote_get($layer_url);

            if (!is_wp_error($response)) {
                $layer_data = json_decode(wp_remote_retrieve_body($response), true);
                $tracks = $layer_data['tracks'] ?? [];
            }
        }
    }

    if ('true' === $random) {
        shuffle($tracks);
    }
    if ($quantity > 0 && count($tracks) > $quantity) {
        $tracks = array_slice($tracks, 0, $quantity);
    }

    ob_start();
?>
    <div class="wm_tracks_grid">
        <?php foreach ($tracks as $track) : ?>
            <div class="wm_grid_track_item">
                <?php
                $name = $track['name'][$language] ?? '';
                $feature_image_url = $track['featureImage']['thumbnail'] ?? '/assets/images/background.jpg';
                $name_url = wm_custom_slugify($name);
                $language_prefix = $language === 'en' ? '/en' : '';
                $track_page_url = "{$language_prefix}/track/{$name_url}/";
                ?>
                <a href="<?= esc_url($track_page_url); ?>">
                    <div class="wm_grid_track_image" style="background-image: url('<?= esc_url($feature_image_url); ?>');"></div>
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