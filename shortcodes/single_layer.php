<?php
if (!is_admin()) {
    add_shortcode('wm_single_layer', 'wm_single_layer_pnfc');
}

function wm_single_layer_pnfc($atts)
{
    extract(shortcode_atts(array(
        'layer' => '',
    ), $atts));

    $app_id = get_option('app_configuration_id');
    $layer_url = "https://geohub.webmapp.it/api/app/webapp/$app_id/layer/$layer";

    $layer = json_decode(file_get_contents($layer_url), true);
}

ob_start();
?>

<?php echo do_shortcode('[wm_grid_track ]'); ?>

<?php
return ob_get_clean();