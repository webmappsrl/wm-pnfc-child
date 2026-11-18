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
 * Impreza carica lo style.css del child theme (handle «theme-style») con la propria versione
 * (?ver=8.22): dopo una modifica del CSS i browser tengono quello vecchio. La versione diventa la
 * data di modifica del file (oc:8940).
 */
function wm_pnfc_child_style_version() {
    $styles = wp_styles();
    $file = get_stylesheet_directory() . '/style.css';
    if (isset($styles->registered['theme-style']) && file_exists($file)) {
        $styles->registered['theme-style']->ver = (string) filemtime($file);
    }
}
add_action('wp_enqueue_scripts', 'wm_pnfc_child_style_version', 100);

/**
 * Contatore dei caratteri sotto il «Riassunto» delle guide nell'editor (oc:8940).
 */
function wm_pnfc_guide_excerpt_counter() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'guide') {
        return;
    }
    $file = get_stylesheet_directory() . '/assets/js/guide-excerpt-counter.js';
    wp_enqueue_script(
        'wm-pnfc-guide-excerpt-counter',
        get_stylesheet_directory_uri() . '/assets/js/guide-excerpt-counter.js',
        array('wp-dom-ready', 'wp-editor', 'wp-i18n'),
        file_exists($file) ? (string) filemtime($file) : null,
        true
    );
    wp_set_script_translations('wm-pnfc-guide-excerpt-counter', 'wm-pnfc-child');
}
add_action('enqueue_block_editor_assets', 'wm_pnfc_guide_excerpt_counter');

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
 * Register taxonomies for Hiking Guides (filterable)
 */
function register_guide_taxonomies() {
    $taxonomies = array(
        'guide_abilitazioni' => array(
            'labels' => array(
                'name'          => __('Attività proposte', 'wm-pnfc-child'),
                'singular_name' => __('Attività proposta', 'wm-pnfc-child'),
                'search_items'  => __('Cerca Attività proposte', 'wm-pnfc-child'),
                'all_items'     => __('Tutte le Attività proposte', 'wm-pnfc-child'),
                'edit_item'     => __('Modifica Attività proposta', 'wm-pnfc-child'),
                'update_item'   => __('Aggiorna Attività proposta', 'wm-pnfc-child'),
                'add_new_item'  => __('Aggiungi Attività proposta', 'wm-pnfc-child'),
                'new_item_name' => __('Nuova Attività proposta', 'wm-pnfc-child'),
                'menu_name'     => __('Attività proposte', 'wm-pnfc-child'),
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'guide-abilitazioni'),
        ),
        'guide_lingue' => array(
            'labels' => array(
                'name'          => __('Lingue parlate', 'wm-pnfc-child'),
                'singular_name' => __('Lingua parlata', 'wm-pnfc-child'),
                'search_items'  => __('Cerca Lingue', 'wm-pnfc-child'),
                'all_items'     => __('Tutte le Lingue', 'wm-pnfc-child'),
                'edit_item'     => __('Modifica Lingua', 'wm-pnfc-child'),
                'update_item'   => __('Aggiorna Lingua', 'wm-pnfc-child'),
                'add_new_item'  => __('Aggiungi Lingua', 'wm-pnfc-child'),
                'new_item_name' => __('Nuova Lingua', 'wm-pnfc-child'),
                'menu_name'     => __('Lingue parlate', 'wm-pnfc-child'),
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'guide-lingue'),
        ),
        'guide_area_operativa' => array(
            'labels' => array(
                'name'          => __('Aree operative', 'wm-pnfc-child'),
                'singular_name' => __('Area operativa', 'wm-pnfc-child'),
                'search_items'  => __('Cerca Aree', 'wm-pnfc-child'),
                'all_items'     => __('Tutte le Aree operative', 'wm-pnfc-child'),
                'edit_item'     => __('Modifica Area', 'wm-pnfc-child'),
                'update_item'   => __('Aggiorna Area', 'wm-pnfc-child'),
                'add_new_item'  => __('Aggiungi Area operativa', 'wm-pnfc-child'),
                'new_item_name' => __('Nuova Area operativa', 'wm-pnfc-child'),
                'menu_name'     => __('Aree operative', 'wm-pnfc-child'),
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'guide-area-operativa'),
        ),
    );

    foreach ($taxonomies as $taxonomy => $args) {
        register_taxonomy($taxonomy, 'guide', $args);
    }
}
add_action('init', 'register_guide_taxonomies', 1);

/**
 * Social della guida, nell'ordine in cui compaiono nel meta box e nella pagina (oc:8940).
 * 'icon' è la classe Font Awesome 5 di Impreza (fab: icone dei marchi, come nell'header); X non ha un'icona in quella versione e usa
 * wm_pnfc_x_icon_svg(). 'profile_base' trasforma un nome di profilo nell'indirizzo del profilo.
 */
function wm_pnfc_guide_social_networks() {
    return array(
        'facebook'  => array('label' => 'Facebook',  'meta_key' => '_guide_facebook',  'icon' => 'fab fa-facebook-f',  'profile_base' => 'https://www.facebook.com/'),
        'instagram' => array('label' => 'Instagram', 'meta_key' => '_guide_instagram', 'icon' => 'fab fa-instagram',   'profile_base' => 'https://www.instagram.com/'),
        'linkedin'  => array('label' => 'LinkedIn',  'meta_key' => '_guide_linkedin',  'icon' => 'fab fa-linkedin-in', 'profile_base' => 'https://www.linkedin.com/in/'),
        'x'         => array('label' => 'X',         'meta_key' => '_guide_x',         'icon' => '',                  'profile_base' => 'https://x.com/'),
        'youtube'   => array('label' => 'YouTube',   'meta_key' => '_guide_youtube',   'icon' => 'fab fa-youtube',     'profile_base' => 'https://www.youtube.com/@'),
        'tiktok'    => array('label' => 'TikTok',    'meta_key' => '_guide_tiktok',    'icon' => 'fab fa-tiktok',      'profile_base' => 'https://www.tiktok.com/@'),
    );
}

/**
 * Corregge un indirizzo social scritto a mano: toglie gli spazi ai bordi, aggiunge https://
 * se manca, trasforma un nome di profilo da solo (wilder_trekk, @wilder_trekk,
 * romagna.selvatica) nell'indirizzo del profilo su quel social.
 * Restituisce null se la riga non è riconoscibile come indirizzo.
 */
function wm_pnfc_normalize_social_url($line, $network) {
    $line = trim((string) $line);
    if ($line === '' || preg_match('/\s/u', $line)) {
        return null;
    }

    $networks = wm_pnfc_guide_social_networks();
    $looks_like_domain = preg_match('#^www\.#i', $line) || preg_match('#\.(com|it|org|net|eu|me|tv)$#i', $line);
    if (strpos($line, '/') === false && !$looks_like_domain) {
        $handle = ltrim($line, '@');
        if (!isset($networks[$network]) || !preg_match('/^[A-Za-z0-9._-]+$/', $handle)) {
            return null;
        }
        return $networks[$network]['profile_base'] . $handle . '/';
    }

    if (!preg_match('#^https?://#i', $line)) {
        $line = 'https://' . ltrim($line, '/');
    }
    $url = esc_url_raw($line, array('http', 'https'));
    $host = wp_parse_url($url, PHP_URL_HOST);
    if ($url === '' || !$host || strpos($host, '.') === false) {
        return null;
    }
    return $url;
}

/**
 * Divide il valore di un campo social (un indirizzo per riga) in indirizzi corretti e righe
 * non riconosciute, senza doppioni e nell'ordine di inserimento.
 */
function wm_pnfc_parse_social_urls($raw, $network) {
    $valid = array();
    $invalid = array();
    foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $url = wm_pnfc_normalize_social_url($line, $network);
        if ($url === null) {
            if (!in_array($line, $invalid, true)) {
                $invalid[] = $line;
            }
        } elseif (!in_array($url, $valid, true)) {
            $valid[] = $url;
        }
    }
    return array('valid' => $valid, 'invalid' => $invalid);
}

/**
 * Meta del meta box «Informazioni di Contatto», da tradurre con WPML.
 */
function wm_pnfc_guide_contact_meta_keys() {
    return array_merge(
        array('_guide_telefono', '_guide_email', '_guide_sito'),
        wp_list_pluck(wm_pnfc_guide_social_networks(), 'meta_key')
    );
}

/**
 * Configure custom fields as translatable in WPML
 */
function configure_guide_contact_fields_for_wpml() {
    if (!function_exists('icl_register_string')) {
        return;
    }

    // List of fields to make translatable (taxonomies are translated via WPML taxonomies)
    $fields = wm_pnfc_guide_contact_meta_keys();

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
    $fields_to_translate = wm_pnfc_guide_contact_meta_keys();

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
        <?php foreach (wm_pnfc_guide_social_networks() as $network => $social) :
            $value = get_post_meta($post->ID, $social['meta_key'], true);
            $parsed = wm_pnfc_parse_social_urls($value, $network);
            $field_id = 'guide_social_' . $network;
            ?>
        <tr>
            <th><label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($social['label']); ?>:</label></th>
            <td>
                <textarea id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" rows="3" class="large-text" placeholder="<?php echo esc_attr($social['profile_base']); ?>…"><?php echo esc_textarea($value); ?></textarea>
                <p class="description"><?php _e('Se la guida ha più profili su questo social, inserisci un indirizzo per riga.', 'wm-pnfc-child'); ?></p>
                <?php if (!empty($parsed['invalid'])) : ?>
                <p class="description" style="color:#b32d2e"><?php _e('Indirizzo non riconosciuto:', 'wm-pnfc-child'); ?> <?php echo esc_html(implode(', ', $parsed['invalid'])); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p class="description"><?php _e('Attività proposte, Lingue parlate e Area operativa si gestiscono dalle rispettive tassonomie nella colonna laterale.', 'wm-pnfc-child'); ?></p>
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
    
    // Save fields (taxonomies are saved by WordPress)
    $fields = array(
        'guide_telefono' => '_guide_telefono',
        'guide_email' => '_guide_email',
        'guide_sito' => '_guide_sito'
    );
    
    // Un campo assente dal form non si tocca: un editor aperto con un form più vecchio
    // cancellerebbe dati salvati nel frattempo (oc:8940). Un campo presente ma vuoto si cancella.
    // Il sito non passa da sanitize_text_field, che toglie le sequenze %XX degli indirizzi
    // (es. città → citt%C3%A0) (oc:8940). update_post_meta vuole i valori con le barre (wp_slash).
    foreach ($fields as $field_name => $meta_key) {
        if (!isset($_POST[$field_name])) {
            continue;
        }
        $raw = wp_unslash($_POST[$field_name]);
        $value = $meta_key === '_guide_sito'
            ? trim(wp_strip_all_tags($raw))
            : sanitize_text_field($raw);
        if ($value === '') {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, wp_slash($value));
        }
    }

    // Social: un indirizzo per riga, corretto dove possibile; le righe non riconosciute restano
    // salvate (nessun dato si perde) ma non compaiono nella pagina.
    foreach (wm_pnfc_guide_social_networks() as $network => $social) {
        $field_name = 'guide_social_' . $network;
        if (!isset($_POST[$field_name])) {
            continue;
        }
        // Niente sanitize_textarea_field: toglierebbe le sequenze %XX degli indirizzi. Le righe
        // valide passano da esc_url_raw in wm_pnfc_normalize_social_url().
        $parsed = wm_pnfc_parse_social_urls(wp_strip_all_tags(wp_unslash($_POST[$field_name])), $network);
        $lines = array_merge($parsed['valid'], $parsed['invalid']);
        if (empty($lines)) {
            delete_post_meta($post_id, $social['meta_key']);
        } else {
            update_post_meta($post_id, $social['meta_key'], wp_slash(implode("\n", $lines)));
        }
    }
}
add_action('save_post', 'save_guide_contact_info');

/**
 * Nome del profilo preso da un indirizzo social, per title e aria-label delle icone
 * (es. https://www.instagram.com/altrevie/ → altrevie).
 */
function wm_pnfc_social_profile_name($url) {
    $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
    $segments = array_values(array_filter(explode('/', $path), 'strlen'));
    if (empty($segments)) {
        return '';
    }
    $name = ltrim(rawurldecode(end($segments)), '@');
    // Indirizzi senza nome del profilo (es. facebook.com/profile.php?id=…)
    return preg_match('/\.php$/i', $name) ? '' : $name;
}

/**
 * Logo di X: Font Awesome 5.15.4 di Impreza non lo contiene (oc:8940).
 */
function wm_pnfc_x_icon_svg() {
    return '<svg class="wm_guide_social_svg" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
}

/**
 * Una riga della card dei contatti, con la struttura di .wm_info_detail_item di wm-package.
 */
function wm_pnfc_contact_row($icon, $label, $value_html) {
    return '<div class="wm_info_detail_item">'
        . '<span class="wm_info_detail_label"><i class="' . esc_attr($icon) . '" aria-hidden="true"></i> ' . esc_html($label) . ':</span>'
        . '<span class="wm_info_detail_value">' . $value_html . '</span>'
        . '</div>';
}

/**
 * Shortcode to display contact information
 * Usage: [guide_contatti id="123"] or [guide_contatti] (for current post)
 *
 * Card nello stile del riquadro «Informazioni» delle tracce di wm-package (oc:8940): «In breve» con
 * il Riassunto, poi «Contatti» con telefono, email, sito, social, Attività proposte, Lingue parlate,
 * Aree operative.
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
    $get_meta = function ($meta_key) use ($post_id, $original_post_id) {
        $value = get_post_meta($post_id, $meta_key, true);
        if (empty($value) && $original_post_id != $post_id) {
            $value = get_post_meta($original_post_id, $meta_key, true);
        }
        return $value;
    };

    $telefono = $get_meta('_guide_telefono');
    $email = $get_meta('_guide_email');
    $sito = $get_meta('_guide_sito');

    // Riassunto: post_excerpt grezzo, mai get_the_excerpt() che lo genererebbe dal contenuto
    $excerpt = trim((string) get_post_field('post_excerpt', $post_id, 'raw'));
    if ($excerpt === '' && $original_post_id != $post_id) {
        $excerpt = trim((string) get_post_field('post_excerpt', $original_post_id, 'raw'));
    }

    // Social: un profilo per riga, icona e nome del profilo, così più profili dello stesso
    // social si distinguono anche sul telefono (oc:8940).
    $social_links = '';
    foreach (wm_pnfc_guide_social_networks() as $network => $social) {
        $parsed = wm_pnfc_parse_social_urls($get_meta($social['meta_key']), $network);
        foreach ($parsed['valid'] as $url) {
            $profile = wm_pnfc_social_profile_name($url);
            $text = $profile !== '' ? $profile : $social['label'];
            $name = $profile !== '' ? $social['label'] . ': ' . $profile : $social['label'];
            $icon = $social['icon'] !== '' ? '<i class="' . esc_attr($social['icon']) . '" aria-hidden="true"></i>' : wm_pnfc_x_icon_svg();
            $social_links .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($name) . '">' . $icon . ' ' . esc_html($text) . '</a>';
        }
    }
    
    // Get taxonomy terms (same display order as before)
    $abilitazioni_terms = get_the_terms($post_id, 'guide_abilitazioni');
    $abilitazioni = $abilitazioni_terms && !is_wp_error($abilitazioni_terms)
        ? implode(', ', wp_list_pluck($abilitazioni_terms, 'name'))
        : '';
    
    $lingue_terms = get_the_terms($post_id, 'guide_lingue');
    $lingue = $lingue_terms && !is_wp_error($lingue_terms)
        ? implode(', ', wp_list_pluck($lingue_terms, 'name'))
        : '';
    
    $area_terms = get_the_terms($post_id, 'guide_area_operativa');
    $area_operativa = $area_terms && !is_wp_error($area_terms)
        ? implode(', ', wp_list_pluck($area_terms, 'name'))
        : '';

    $rows = '';
    if (!empty($telefono)) {
        $rows .= wm_pnfc_contact_row('fa fa-phone', __('Telefono', 'wm-pnfc-child'), '<a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $telefono)) . '">' . esc_html($telefono) . '</a>');
    }
    if (!empty($email)) {
        $rows .= wm_pnfc_contact_row('fa fa-envelope', __('Email', 'wm-pnfc-child'), '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>');
    }
    if (!empty($sito)) {
        $rows .= wm_pnfc_contact_row('fa fa-external-link-alt', __('Sito', 'wm-pnfc-child'), '<a href="' . esc_url($sito) . '" target="_blank" rel="noopener">' . esc_html($sito) . '</a>');
    }
    if ($social_links !== '') {
        $rows .= wm_pnfc_contact_row('fa fa-share-alt', __('Social', 'wm-pnfc-child'), '<span class="wm_guide_social">' . $social_links . '</span>');
    }
    if (!empty($abilitazioni)) {
        $rows .= wm_pnfc_contact_row('fa fa-certificate', __('Attività proposte', 'wm-pnfc-child'), esc_html($abilitazioni));
    }
    if (!empty($lingue)) {
        $rows .= wm_pnfc_contact_row('fa fa-language', __('Lingue parlate', 'wm-pnfc-child'), esc_html($lingue));
    }
    if (!empty($area_operativa)) {
        $rows .= wm_pnfc_contact_row('fa fa-map-marker-alt', __('Aree operative', 'wm-pnfc-child'), esc_html($area_operativa));
    }

    // Check if there are data to display
    if ($excerpt === '' && $rows === '') {
        return '';
    }

    // Due card: «In breve» con il Riassunto, poi «Contatti»; ognuna solo se ha qualcosa da mostrare.
    $output = '<div class="wm_guide_contact_info">';
    if ($excerpt !== '') {
        $output .= '<div class="wm_info_details wm_guide_summary">';
        $output .= '<h2 class="wm_info_details_title">' . esc_html__('In breve', 'wm-pnfc-child') . '</h2>';
        $output .= '<div class="wm_guide_excerpt">' . nl2br(esc_html($excerpt)) . '</div>';
        $output .= '</div>';
    }
    if ($rows !== '') {
        $output .= '<div class="wm_info_details">';
        $output .= '<h2 class="wm_info_details_title">' . esc_html__('Contatti', 'wm-pnfc-child') . '</h2>';
        $output .= '<div class="wm_info_details_grid">' . $rows . '</div>';
        $output .= '</div>';
    }
    $output .= '</div>';
    
    return $output;
}
add_shortcode('guide_contatti', 'guide_contatti_shortcode');

/**
 * true se la guida ha almeno una foto esistente nella galleria (meta us_tile_additional_image).
 * Senza foto lo slider di Impreza non sparisce: mostra 3 immagini qualsiasi della libreria media
 * (us-core/templates/elements/image_slider.php), per questo la pagina deve saperlo (oc:8940).
 */
function wm_pnfc_guide_has_gallery($post_id) {
    $ids = array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, 'us_tile_additional_image', true))));
    if (empty($ids)) {
        return false;
    }
    return (bool) get_posts(array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => 'image',
        'include'        => $ids,
        'numberposts'    => 1,
        'fields'         => 'ids',
    ));
}

/**
 * Classe sul body delle guide senza galleria: il CSS nasconde lo slider (oc:8940).
 */
function wm_pnfc_guide_body_class($classes) {
    if (is_singular('guide') && !wm_pnfc_guide_has_gallery(get_queried_object_id())) {
        $classes[] = 'wm-guide-no-gallery';
    }
    return $classes;
}
add_filter('body_class', 'wm_pnfc_guide_body_class');

