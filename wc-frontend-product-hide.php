<?php
/*
    Plugin Name: WooCommerce frontend product hide
    Plugin URI: https://www.purin.at/opensource/woocommerce-frontend-product-hide/
    Version: 1.0.0
    Author: Christoph Purin
    Author URI: https://www.purin.at
    License: MIT
    Text Domain: wc-frontend-product-hide
    Domain Path: languages
    */

add_action('plugins_loaded', 'load_product_hide_textdomain');
function load_product_hide_textdomain()
{
    load_plugin_textdomain('wc-frontend-product-hide', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}


add_action('admin_menu', 'my_add_custom_settings_page');

function my_add_custom_settings_page()
{
    add_submenu_page(
        'woocommerce',
        __('Product hide settings', 'wc-frontend-product-hide'),
        __('Product hide settings', 'wc-frontend-product-hide'),
        'manage_options',
        'my-custom-settings',
        'my_custom_settings_page'
    );
}

function my_custom_settings_page()
{
?>
    <div class="wrap">
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

function get_exclude_product_ids()
{
    $exclude_product_ids = get_option('exclude_product_ids');
    if (!empty($exclude_product_ids)) {
        $exclude_product_ids = array_map('intval', $exclude_product_ids);
        return $exclude_product_ids;
    }
    return array();
}

add_action('pre_get_posts', 'exclude_products_from_loop');

function exclude_products_from_loop($query)
{
    if ((is_shop() || is_product_category()) && $query->is_main_query()) {
        $excluded_ids = get_exclude_product_ids();
        if (!empty($excluded_ids)) {
            $query->set('post__not_in', $excluded_ids);
        }
    }
}
