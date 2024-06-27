<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

require('shortcodes/single_track.php');
require('shortcodes/single_poi.php');
require('shortcodes/grid_track.php');
require('shortcodes/grid_poi.php');
require('shortcodes/single_layer.php');

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if (!function_exists('chld_thm_cfg_locale_css')) :
    function chld_thm_cfg_locale_css($uri)
    {
        if (empty($uri) && is_rtl() && file_exists(get_template_directory() . '/rtl.css'))
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter('locale_stylesheet_uri', 'chld_thm_cfg_locale_css');

// END ENQUEUE PARENT ACTION

//Swiper Slider CSS da CDN
function child_theme_enqueue_swiper()
{
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_swiper');

// Lightbox2 CSS and JS from CDN
function child_theme_enqueue_lightbox2_cdn()
{
    wp_enqueue_style('lightbox2-css', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css');
    wp_enqueue_script('lightbox2-js', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js', array('jquery'), '', true);
    add_action('wp_footer', 'configure_lightbox2');
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_lightbox2_cdn');

// Configuration Lightbox2
function configure_lightbox2()
{
?>
    <script>
        lightbox.option({
            'fadeDuration': 50,
            'resizeDuration': 50,
            'wrapAround': true
        });
    </script>
<?php
}

//Font awesome
function load_font_awesome()
{
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'load_font_awesome');

//Slug
function wm_custom_slugify($title)
{
    $title = iconv('UTF-8', 'ASCII//TRANSLIT', $title);
    $title = str_replace('–', '-', $title);
    $title = str_replace("’", '', $title);
    $title = preg_replace('!\s+!', ' ', $title);
    $slug = sanitize_title_with_dashes($title);
    return $slug;
}

// Add custom script to change menu link based on device
function add_custom_menu_script()
{
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var userAgent = navigator.userAgent || navigator.vendor || window.opera;
            var menuLink = document.querySelector('.menu-item-90 a');

            if (menuLink) {
                if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
                    // iOS
                    menuLink.href = "https://apps.apple.com/it/app/pnfc-trekking-map/id1053420140";
                } else if (/android/i.test(userAgent)) {
                    // Android
                    menuLink.href = "https://play.google.com/store/apps/details?id=it.net7.parcoforestecasentinesi";
                } else {
                    // Not mobile
                    menuLink.href = "https://maps.parcoforestecasentinesi.it/#/map";
                }
                menuLink.target = "_blank";
            }
        });
    </script>
<?php
}
add_action('wp_footer', 'add_custom_menu_script');
