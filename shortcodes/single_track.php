<?php
if (!is_admin()) {
    add_shortcode('wm_single_track', 'wm_single_track_maremma');
}

function wm_single_track_maremma($atts)
{

    if (defined('ICL_LANGUAGE_CODE')) {
        $language = ICL_LANGUAGE_CODE;
    } else {
        $language = 'it';
    }

    extract(shortcode_atts(array(
        'track_id' => '',
        'activity' => ''
    ), $atts));

    $geojson_url = "https://geohub.webmapp.it/api/app/elbrus/1/geojson/$track_id.geojson";
    $json_url = "https://geohub.webmapp.it/api/app/elbrus/1/geojson/$track_id.json";
    $track = json_decode(file_get_contents($json_url), TRUE);
    // echo '<pre>';
    // print_r($track);
    // echo '</pre>';
    $mapping_tickets = json_decode(file_get_contents(get_stylesheet_directory_uri() . '/assets/track_ticket_mapping.json'), TRUE);
    // $mapping_tickets = json_decode(file_get_contents('http://parco-maremma.local/wp-content/themes/wm-maremma-child/assets/track_ticket_mapping.json'), TRUE);
    if (array_key_exists('excerpt', $track) && array_key_exists($language, $track['excerpt'])) {
        $excerpt = $track['excerpt'][$language];
    } else {
        $excerpt = null;
    }
    $description = $track['description'][$language];
    $title = $track['name'][$language];
    $featured_image = $track['image']['sizes']['1440x500'];
    $gallery = array_key_exists('imageGallery', $track) ? $track['imageGallery'] : null;
    $gpx = $track['gpx'];

    $mapping = array();
    // mapping the tickets section
    foreach ($mapping_tickets as $track => $info) {
        if (strtolower($track) == strtolower($track_id . '_' . $language)) {
            $mapping = $info;
        }
    }
    ob_start();
?>

    <section class="l-section wpb_row height_small wm_track_breadcrumb_section">
        <div class="l-section-h i-cf">
            <div class="pm-breadcrumb-yoast">
                <div class="wpb_wrapper">
                    <?php echo do_shortcode('[wpseo_breadcrumb]'); ?>
                </div>
            </div>
        </div>
    </section>
    <section class="l-section wpb_row height_small with_img with_overlay wm_track_header_section">

        <div class="l-section-img loaded pm-header-image" style="background-image: url(<?= $featured_image ?>);background-repeat: no-repeat;">
        </div>
        <div class="l-section-overlay" style="background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.3) 100%)"></div>
        <div class="l-section-h i-cf wm_track_header_wrapper">
            <h3 class="align_left wm_track_header_taxonomy">
                <?= (($language == 'it') ? __('Percorsi', 'wm-child-maremma') . ' ' : '') . $activity ?></h3>
            <h1 class="align_left wm_track_header_title">
                <?= $title ?></h1>
        </div>
    </section>
    <div class="wm_track_body_section">
        <div class="wm_track_body_map_wrapper">
            <div class="wm_track_body_map_title">
                <?php if ($excerpt) { ?>
                    <div class="wm_track_body_excerpt">
                        <h2><?php echo $excerpt; ?></h2>
                    </div>
                <?php } ?>
            </div>
            <div class="wm_track_body_map">
                <?php
                echo do_shortcode('[wm-embedmaps geojson_url="' . $geojson_url . '" height="500px" lang="' . $language . '" related_poi_click_behaviour="open" show_related_pois="true" fullscreen="true"  hide_taxonomy_filters="true"]');
                ?>
            </div>
        </div>
        <?php if ($description) { ?>
            <div class="wm_track_body_description">
                <?php echo $description; ?>
            </div>
        <?php } ?>
        <div class="wm_track_body_gallery">
            <?php if (is_array($gallery) && !empty($gallery)) : ?>
                <div class="slick-slider">
                    <?php foreach ($gallery as $image) : ?>
                        <div>
                            <img src="<?= esc_url($image['sizes']['400x200']) ?>" alt="" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="wm_track_body_sidebar_wrapper">
            <div class="wm_track_body_map_details">
                <div class="wm_track_detail">
                    <p class="track_sidebar_label"><?= __('Tecnical info', 'wm-child-maremma') ?></p>
                </div>
                <?php
                echo do_shortcode('[wm-embedmaps-technical-info feature_id="' . $track_id . '-' . $track_id . '" config="ele_from,ele_to,ele_max,ele_min,distance,duration_forward,ascent,descent,difficulty,scale"]');
                ?>
                <div class="wm_track_body_map_elevation">
                    <p class="track_sidebar_label"><?= __('Elevation chart', 'wm-child-maremma') ?></p>
                    <div class="wm-elevation-chart">
                        <?php
                        echo do_shortcode('[wm-embedmaps-elevation-chart feature_id="' . $track_id . '-' . $track_id . '"]');
                        ?>
                    </div>
                </div>
                <div class="wm_track_body_download">
                    <a class="icon_atleft" href="<?= $gpx ?>"><?= __('Download GPX', 'wm-child-maremma') ?></span></a>
                </div>
            </div>
        </div>
        <div class="wm_track_body_content_wrapper">
            <?php
            if (!empty($mapping)) {
            ?><div class="wm_track_body_ticket">
                    <p class="ticket_text"><?= $mapping['description'] ?></p><?php
                                                                                if (array_key_exists('calendar', $mapping)) {
                                                                                ?><div class="single_track_ticket_btn">
                            <a class="w-btn us-btn-style_1" href="<?= $mapping['calendar'] ?>"><span class="w-btn-label"><?= __('Go to calendar', 'wm-child-maremma') ?></span></a>
                        </div>
                        <a class="single_track_ticket_link" href="<?= $mapping['subscription'] ?>"><span class="w-btn-label"><?= __('Subscription and promotions', 'wm-child-maremma') ?></span></a>
                    <?php
                                                                                }
                    ?>
                </div><?php
                    }
                    if (array_key_exists('purchase', $mapping)) {
                        ?>
                <div class="single_track_ticket_btn">
                    <a class="w-btn us-btn-style_6 purchase-button" href="<?= esc_url($mapping['purchase']); ?>">
                        <span class="w-btn-label"><?= esc_html__('Purchase', 'wm-child-maremma'); ?></span>
                        <img src="/wp-content/uploads/2023/11/Tracciato-95.png" alt="Arrow Icon" class="pm-arrow-icon">
                    </a>
                </div>
                <?php
                    }
                    if (array_key_exists('subscription', $mapping)) {
                ?><?php
                    }
                    ?>
        </div>

    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.slick-slider').slick({
                dots: false,
                infinite: true,
                speed: 300,
                slidesToShow: 1,
                adaptiveHeight: false,
                variableWidth: false,
                centerMode: false,
            });
        });
    </script>

<?php

    return ob_get_clean();
}
