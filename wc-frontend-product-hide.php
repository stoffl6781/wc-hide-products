<?php
/*
    Plugin Name: WC frontend product hide
    Plugin URI: https://www.purin.at/opensource/woocommerce-frontend-product-hide/
    Version: 1.0.0
    Author: Christoph Purin
    Author URI: https://www.purin.at
    License: MIT
    Description: WC hide products in frontend
    Text Domain: wc-frontend-product-hide
    Domain Path: languages
    GitHub Plugin URI: https://github.com/stoffl6781/woocommerce-fronted-product-hide/
    GitHub Branch:     master
*/

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action('plugins_loaded', 'load_product_hide_textdomain');
function load_product_hide_textdomain()
{
    load_plugin_textdomain('wc-frontend-product-hide', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

/**
 * Activation hook.
 */

function wcfpd_activate()
{
    // Optionsfeld erstellen oder Standardwerte festlegen
    add_option('exclude_product_ids', '');
    // Weitere Initialisierungslogik hier einfügen
}

register_activation_hook(__FILE__, 'wcfpd_activate');

/**
 * Deactivation hook.
 */

function wcfpd_deactivate() {
	delete_option('exclude_product_ids');
}

register_deactivation_hook( __FILE__, 'wcfpd_deactivate' );

/**
 * Register frontend option page
 */

add_action('admin_menu', 'wc_frontend_product_hide_admin_menu');

function wc_frontend_product_hide_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        __('Product hide settings', 'wc-frontend-product-hide'),
        __('Product hide settings', 'wc-frontend-product-hide'),
        'manage_options',
        'my-custom-settings',
        'wc_frontend_product_hide_admin_page'
    );
}

function wc_frontend_product_hide_admin_page()
{
?>
    <div class="wrap">
        <?php
        if (isset($_GET['settings-updated'])) {
            settings_errors('wc-frontend-product-hide');
        }
        ?>

        <h1><?php echo esc_html(__('My custom settings', 'wc-frontend-product-hide')); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('woocommerce');
            do_settings_sections('woocommerce');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

add_action('admin_init', 'exclude_products_options_init');

function exclude_products_options_init()
{
    add_settings_section('exclude_products_section', 'Ausgeschlossene Produkte', 'exclude_products_section_callback', 'woocommerce');
    add_settings_field('exclude_product_ids_field', 'Produkt-IDs', 'exclude_product_ids_field_callback', 'woocommerce', 'exclude_products_section');
    register_setting('woocommerce', 'exclude_product_ids', 'sanitize_callback');

    if (isset($_GET['settings-updated'])) {
        $settings_saved = $_GET['settings-updated'];
        if ($settings_saved) {
            // Optionen erfolgreich gespeichert
            add_settings_error('wc-frontend-product-hide', 'settings_saved', __('Settings saved.', 'wc-frontend-product-hide'), 'updated');
        } else {
            // Fehler beim Speichern der Optionen
            add_settings_error('wc-frontend-product-hide', 'settings_not_saved', __('Failed to save settings.', 'wc-frontend-product-hide'), 'error');
        }
    }
}


function exclude_products_section_callback()
{
    echo esc_html(__('Enter product IDs here to exclude products.', 'wc-frontend-product-hide'));
}


// Callback
function exclude_product_ids_field_callback()
{
    $exclude_product_ids = get_option('exclude_product_ids');
    $exclude_product_ids = $exclude_product_ids ? $exclude_product_ids : '';

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
    );

    $products = new WP_Query($args);

    echo '<select id="exclude_product_ids" name="exclude_product_ids[]" multiple>';

    while ($products->have_posts()) {
        $products->the_post();
        $product_id = get_the_ID();
        $product_title = get_the_title();

        $selected = in_array($product_id, $exclude_product_ids) ? 'selected' : '';

        echo '<option value="' . esc_attr($product_id) . '" ' . $selected . '>' . esc_html($product_title) . '</option>';
    }

    echo '</select>';

    wp_reset_postdata();
}

/**
 * Get exclude list
 */
function get_exclude_product_ids()
{
    $exclude_product_ids = get_option('exclude_product_ids');
    if (!empty($exclude_product_ids)) {
        $exclude_product_ids = array_map('intval', $exclude_product_ids);
        return $exclude_product_ids;
    }
    return array();
}

/**
 * Exclude from Shop
 */

 add_action('pre_get_posts', 'exclude_products_from_loop');

 function exclude_products_from_loop($query)
 {
     if ((is_shop() || is_product_category()) && $query->is_main_query() && !is_admin()) {
         $excluded_ids = get_exclude_product_ids();
         if (!empty($excluded_ids)) {
             $query->set('post__not_in', $excluded_ids);
         }
     }
 }
 
 add_filter('the_title', 'modify_product_title_for_admin', 10, 2);
 
 function modify_product_title_for_admin($title, $post_id) {
     if (is_admin() && current_user_can('administrator')) {
         $excluded_ids = get_exclude_product_ids();
         if (in_array($post_id, $excluded_ids)) {
             $title = ' (hidden Product) ' . $title;
         }
     }
     return $title;
 }
