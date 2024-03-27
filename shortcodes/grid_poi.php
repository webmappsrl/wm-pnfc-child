<?php

add_shortcode('wm_grid_poi', 'wm_grid_poi');

function wm_grid_poi($atts)
{
    if (!is_admin()) {
        if (defined('ICL_LANGUAGE_CODE')) {
            $language = ICL_LANGUAGE_CODE;
        } else {
            $language = 'it';
        }

        extract(shortcode_atts(array(
            'poi_type_id' => '',
            'quantity' => -1,
            'random' => 'false'
        ), $atts));

        $poi_data = [];

        if ($poi_type_id) {
            $poi_url = "https://geohub.webmapp.it/api/app/webapp/49/taxonomies/poi_type/$poi_type_id";
            $response = wp_remote_get($poi_url);
            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                $poi_data = $data ?? [];
            }
        }
        if ('true' === $random) {
            shuffle($poi_data);
        }
        if ($quantity > 0 && count($poi_data) > $quantity) {
            $poi_data = array_slice($poi_data, 0, $quantity);
        }
        ob_start();
?>
        <div class="wm_poi_grid">
            <?php foreach ($poi_data as $poi) : ?>
                <div class="wm_grid_poi_item">
                    <?php
                    $name = $poi['name'][$language] ?? '';
                    $feature_image_url = $poi['featureImage']['thumbnail'] ?? '/assets/images/background.jpg';
                    $name_url = wm_custom_slugify($name);
                    $language_prefix = $language === 'en' ? '/en' : '';
                    $poi_page_url = "{$language_prefix}/poi/{$name_url}/";
                    $icon = '<span class="fas fa-map-marker-alt"></span>';
                    ?>
                    <a href="<?= esc_url($poi_page_url); ?>">
                        <div class="wm_grid_poi_image" style="background-image: url('<?= esc_url($feature_image_url); ?>');">
                        </div>
                        <?php if ($name) : ?>
                            <div class="wm_grid_poi_name"><?= $icon; ?> <?= esc_html($name); ?></div>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
<?php
        echo ob_get_clean();
    } else {
        return;
    }
}
