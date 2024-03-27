<?php
if (!is_admin()) {
    add_shortcode('wm_single_layer', 'wm_single_layer_pnfc');
}


function wm_single_layer_pnfc($atts)
{
    if (defined('ICL_LANGUAGE_CODE')) {
        $language = ICL_LANGUAGE_CODE;
    } else {
        $language = 'it';
    }
    extract(shortcode_atts(array(
        'layer' => '',
    ), $atts));


    $app_id = get_option('app_configuration_id');
    $layer_url = "https://geohub.webmapp.it/api/app/webapp/$app_id/layer/$layer";
    $layer = json_decode(file_get_contents($layer_url), true);
    // echo '<pre>';
    // print_r($track_ids);
    // echo '</pre>';
    $featured_image = null;
    $title = null;
    $description = null;

    if ($layer) {
        $featured_image_url = $layer['featureImage']['url'] ?? get_stylesheet_directory_uri() . '/assets/images/background.jpg';
        $featured_image = $layer['featureImage']['sizes']['1440x500'] ?? $layer['featureImage']['sizes']['400x200'] ?? $featured_image_url;
        $title = $layer['title'][$language] ?? null;
        $description = $layer['description'][$language] ?? null;
        $track_ids = isset($layer['tracks']) ? array_map(function ($track) {
            return $track['id'];
        }, $layer['tracks']) : [];
        $track_ids_string = implode(',', $track_ids);
    }


    ob_start();
?>
    <section class="l-section wpb_row height_small with_img with_overlay wm_header_section">
        <div class="l-section-img loaded wm-header-image" style="background-image: url(<?= $featured_image ?>);background-repeat: no-repeat;">
        </div>
        <div class="l-section-h i-cf wm_header_wrapper">
        </div>
    </section>

    <div class="wm_body_section">
        <div class="wm_body_map_wrapper">
            <?php if ($title) { ?>
                <h1 class="align_left wm_header_title">
                    <?= $title ?>
                </h1>
            <?php } ?>
        </div>
    </div>

    <div class="wm_body_section">
        <?php echo do_shortcode("[wm_grid_track ids='{$track_ids_string}']"); ?>
    </div>
<?php


    return ob_get_clean();
}
?>