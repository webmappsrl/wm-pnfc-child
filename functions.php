<?php
/**
 * Theme functions and definitions
 * PNFC Child Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Hiking Guides Custom Post Type
 */
function create_guide_post_type() {
    $labels = array(
        'name'                  => _x('Guide escursionistiche', 'Post Type General Name', 'wm-pnfc-child'),
        'singular_name'         => _x('Guide escursionistiche', 'Post Type Singular Name', 'wm-pnfc-child'),
        'menu_name'             => __('Guide escursionistiche', 'wm-pnfc-child'),
        'name_admin_bar'        => __('Guide escursionistiche', 'wm-pnfc-child'),
        'archives'              => __('Archivi Guide escursionistiche', 'wm-pnfc-child'),
        'attributes'            => __('Attributi Guide escursionistiche', 'wm-pnfc-child'),
        'parent_item_colon'     => __('Parente Guide escursionistiche:', 'wm-pnfc-child'),
        'all_items'             => __('Tutte le Guide escursionistiche', 'wm-pnfc-child'),
        'add_new_item'          => __('Aggiungi Nuova Guide escursionistiche', 'wm-pnfc-child'),
        'add_new'               => __('Aggiungi Nuova', 'wm-pnfc-child'),
        'new_item'              => __('Nuova Guide escursionistiche', 'wm-pnfc-child'),
        'edit_item'             => __('Modifica Guide escursionistiche', 'wm-pnfc-child'),
        'update_item'           => __('Aggiorna Guide escursionistiche', 'wm-pnfc-child'),
        'view_item'             => __('Vedi Guide escursionistiche', 'wm-pnfc-child'),
        'view_items'            => __('Vedi Guide escursionistiche', 'wm-pnfc-child'),
        'search_items'          => __('Cerca Guide escursionistiche', 'wm-pnfc-child'),
        'not_found'             => __('Non trovato', 'wm-pnfc-child'),
        'not_found_in_trash'    => __('Non trovato nel cestino', 'wm-pnfc-child'),
        'featured_image'        => __('Immagine in Evidenza', 'wm-pnfc-child'),
        'set_featured_image'    => __('Imposta immagine in evidenza', 'wm-pnfc-child'),
        'remove_featured_image' => __('Rimuovi immagine in evidenza', 'wm-pnfc-child'),
        'use_featured_image'    => __('Usa come immagine in evidenza', 'wm-pnfc-child'),
        'insert_into_item'      => __('Inserisci nella Guide escursionistiche', 'wm-pnfc-child'),
        'uploaded_to_this_item' => __('Caricato su questa Guide escursionistiche', 'wm-pnfc-child'),
        'items_list'            => __('Lista Guide escursionistiche', 'wm-pnfc-child'),
        'items_list_navigation' => __('Navigazione Lista Guide escursionistiche', 'wm-pnfc-child'),
        'filter_items_list'     => __('Filtra Lista Guide escursionistiche', 'wm-pnfc-child'),
    );
    
    $args = array(
        'label'                 => __('Guide escursionistiche', 'wm-pnfc-child'),
        'description'           => __('Post Type per le Guide escursionistiche', 'wm-pnfc-child'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-location',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
        'rewrite'               => array('slug' => 'guide-escursionistiche'),
    );
    
    register_post_type('guide', $args);
}
add_action('init', 'create_guide_post_type', 0);

/**
 * Configure custom fields as translatable in WPML
 */
function configure_guide_contact_fields_for_wpml() {
    if (!function_exists('icl_register_string')) {
        return;
    }
    
    // List of fields to make translatable
    $fields = array(
        '_guide_telefono',
        '_guide_email',
        '_guide_sito',
        '_guide_abilitazioni',
        '_guide_lingue',
        '_guide_area_operativa'
    );
    
    // Configure fields as translatable in WPML settings
    global $iclTranslationManagement;
    if (isset($iclTranslationManagement)) {
        foreach ($fields as $field) {
            // Set field as translatable (value 2 = Translate)
            // 0 = Do nothing, 1 = Copy, 2 = Translate
            if (!isset($iclTranslationManagement->settings['custom_fields_translation'][$field])) {
                $iclTranslationManagement->settings['custom_fields_translation'][$field] = 2;
            }
        }
        $iclTranslationManagement->save_settings();
    }
}
add_action('admin_init', 'configure_guide_contact_fields_for_wpml');

/**
 * Filter to force fields as translatable in WPML
 */
function wpml_guide_contact_fields_translation_settings($settings, $field_name) {
    $fields_to_translate = array(
        '_guide_telefono',
        '_guide_email',
        '_guide_sito',
        '_guide_abilitazioni',
        '_guide_lingue',
        '_guide_area_operativa'
    );
    
    if (in_array($field_name, $fields_to_translate)) {
        return 2; // 2 = Translate
    }
    
    return $settings;
}
add_filter('wpml_custom_field_translation_settings', 'wpml_guide_contact_fields_translation_settings', 10, 2);

/**
 * Add meta box for Hiking Guides contact information
 */
function add_guide_contact_meta_box() {
    add_meta_box(
        'guide_contact_info',
        __('Informazioni di Contatto', 'wm-pnfc-child'),
        'render_guide_contact_meta_box',
        'guide',
        'normal',
        'low'
    );
}
add_action('add_meta_boxes', 'add_guide_contact_meta_box');

/**
 * Render the meta box form
 */
function render_guide_contact_meta_box($post) {
    wp_nonce_field('save_guide_contact_info', 'guide_contact_info_nonce');
    
    $telefono = get_post_meta($post->ID, '_guide_telefono', true);
    $email = get_post_meta($post->ID, '_guide_email', true);
    $sito = get_post_meta($post->ID, '_guide_sito', true);
    $abilitazioni = get_post_meta($post->ID, '_guide_abilitazioni', true);
    $lingue = get_post_meta($post->ID, '_guide_lingue', true);
    $area_operativa = get_post_meta($post->ID, '_guide_area_operativa', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="guide_telefono"><?php _e('Contatto telefonico:', 'wm-pnfc-child'); ?></label></th>
            <td><input type="tel" id="guide_telefono" name="guide_telefono" value="<?php echo esc_attr($telefono); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="guide_email"><?php _e('Email:', 'wm-pnfc-child'); ?></label></th>
            <td><input type="email" id="guide_email" name="guide_email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="guide_sito"><?php _e('Url:', 'wm-pnfc-child'); ?></label></th>
            <td><input type="url" id="guide_sito" name="guide_sito" value="<?php echo esc_attr($sito); ?>" class="regular-text" placeholder="https://" /></td>
        </tr>
        <tr>
            <th><label for="guide_abilitazioni"><?php _e('Abilitazioni:', 'wm-pnfc-child'); ?></label></th>
            <td><textarea id="guide_abilitazioni" name="guide_abilitazioni" rows="3" class="large-text"><?php echo esc_textarea($abilitazioni); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="guide_lingue"><?php _e('Lingue parlate:', 'wm-pnfc-child'); ?></label></th>
            <td><input type="text" id="guide_lingue" name="guide_lingue" value="<?php echo esc_attr($lingue); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="guide_area_operativa"><?php _e('Area operativa:', 'wm-pnfc-child'); ?></label></th>
            <td><textarea id="guide_area_operativa" name="guide_area_operativa" rows="3" class="large-text"><?php echo esc_textarea($area_operativa); ?></textarea></td>
        </tr>
    </table>
    <script>
    jQuery(document).ready(function($) {
        // Move meta box after image gallery
        var contactBox = $('#guide_contact_info');
        var galleryBox = $('#postimagediv, #gallerydiv, .gallery-meta-box');
        
        if (contactBox.length && galleryBox.length) {
            contactBox.insertAfter(galleryBox.last());
        } else {
            // If gallery not found, try to move it after featured image
            var featuredImage = $('#postimagediv');
            if (featuredImage.length) {
                contactBox.insertAfter(featuredImage);
            }
        }
    });
    </script>
    <?php
}

/**
 * Save meta box data
 */
function save_guide_contact_info($post_id) {
    // Verify nonce
    if (!isset($_POST['guide_contact_info_nonce']) || !wp_verify_nonce($_POST['guide_contact_info_nonce'], 'save_guide_contact_info')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Check post type
    if (get_post_type($post_id) !== 'guide') {
        return;
    }
    
    // Save fields
    $fields = array(
        'guide_telefono' => '_guide_telefono',
        'guide_email' => '_guide_email',
        'guide_sito' => '_guide_sito',
        'guide_abilitazioni' => '_guide_abilitazioni',
        'guide_lingue' => '_guide_lingue',
        'guide_area_operativa' => '_guide_area_operativa'
    );
    
    foreach ($fields as $field_name => $meta_key) {
        if (isset($_POST[$field_name])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field_name]));
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }
}
add_action('save_post', 'save_guide_contact_info');

/**
 * Shortcode to display contact information
 * Usage: [guide_contatti id="123"] or [guide_contatti] (for current post)
 */
function guide_contatti_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => get_the_ID(),
    ), $atts);
    
    $post_id = intval($atts['id']);
    
    if (get_post_type($post_id) !== 'guide') {
        return '';
    }
    
    // If WPML is active, also search for meta in original post
    $original_post_id = $post_id;
    if (function_exists('icl_object_id')) {
        $default_language = apply_filters('wpml_default_language', null);
        $original_post_id = icl_object_id($post_id, 'guide', true, $default_language);
    }
    
    // Search for meta first in current post, then in original post
    $telefono = get_post_meta($post_id, '_guide_telefono', true);
    if (empty($telefono) && $original_post_id != $post_id) {
        $telefono = get_post_meta($original_post_id, '_guide_telefono', true);
    }
    
    $email = get_post_meta($post_id, '_guide_email', true);
    if (empty($email) && $original_post_id != $post_id) {
        $email = get_post_meta($original_post_id, '_guide_email', true);
    }
    
    $sito = get_post_meta($post_id, '_guide_sito', true);
    if (empty($sito) && $original_post_id != $post_id) {
        $sito = get_post_meta($original_post_id, '_guide_sito', true);
    }
    
    $abilitazioni = get_post_meta($post_id, '_guide_abilitazioni', true);
    if (empty($abilitazioni) && $original_post_id != $post_id) {
        $abilitazioni = get_post_meta($original_post_id, '_guide_abilitazioni', true);
    }
    
    $lingue = get_post_meta($post_id, '_guide_lingue', true);
    if (empty($lingue) && $original_post_id != $post_id) {
        $lingue = get_post_meta($original_post_id, '_guide_lingue', true);
    }
    
    $area_operativa = get_post_meta($post_id, '_guide_area_operativa', true);
    if (empty($area_operativa) && $original_post_id != $post_id) {
        $area_operativa = get_post_meta($original_post_id, '_guide_area_operativa', true);
    }
    
    // Check if there are data to display
    if (empty($telefono) && empty($email) && empty($sito) && 
        empty($abilitazioni) && empty($lingue) && empty($area_operativa)) {
        return '';
    }
    
    $output = '<div class="wm_guide_contact_info">';
    
    if (!empty($telefono)) {
        $output .= '<div class="wm_guide_contact_item">';
        $output .= '<span class="wm_guide_contact_icon fa fa-phone"></span>';
        $output .= '<span class="wm_guide_contact_text"><a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $telefono)) . '">' . esc_html($telefono) . '</a></span>';
        $output .= '</div>';
    }
    
    if (!empty($email)) {
        $output .= '<div class="wm_guide_contact_item">';
        $output .= '<span class="wm_guide_contact_icon fa fa-envelope"></span>';
        $output .= '<span class="wm_guide_contact_text"><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></span>';
        $output .= '</div>';
    }
    
    if (!empty($sito)) {
        $url = esc_url($sito);
        $output .= '<div class="wm_guide_contact_item">';
        $output .= '<span class="wm_guide_contact_icon fa fa-external-link-alt"></span>';
        $output .= '<span class="wm_guide_contact_text"><a href="' . $url . '" target="_blank" rel="noopener">' . esc_html($sito) . '</a></span>';
        $output .= '</div>';
    }
    
    if (!empty($abilitazioni)) {
        $output .= '<div class="wm_guide_contact_item">';
        $output .= '<span class="wm_guide_contact_icon fa fa-certificate"></span>';
        $output .= '<span class="wm_guide_contact_text">' . nl2br(esc_html($abilitazioni)) . '</span>';
        $output .= '</div>';
    }
    
    if (!empty($lingue)) {
        $output .= '<div class="wm_guide_contact_item">';
        $output .= '<span class="wm_guide_contact_icon fa fa-language"></span>';
        $output .= '<span class="wm_guide_contact_text">' . esc_html($lingue) . '</span>';
        $output .= '</div>';
    }
    
    if (!empty($area_operativa)) {
        $output .= '<div class="wm_guide_contact_item">';
        $output .= '<span class="wm_guide_contact_icon fa fa-map-marker-alt"></span>';
        $output .= '<span class="wm_guide_contact_text">' . nl2br(esc_html($area_operativa)) . '</span>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode('guide_contatti', 'guide_contatti_shortcode');

