<?php
/**
 * Plugin Name: WooCommerce Bulk Price Editor
 * Plugin URI: https://github.com/yourusername/woo-bulk-price-editor
 * Description: Hromadně měňte ceny produktů podle kategorií pro jednoduché i variantní produkty
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: woo-bulk-price-editor
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_BULK_PRICE_EDITOR_VERSION', '1.0.0');
define('WC_BULK_PRICE_EDITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_BULK_PRICE_EDITOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if WooCommerce is active
 */
function wc_bulk_price_editor_check_woocommerce()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wc_bulk_price_editor_woocommerce_missing_notice');
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    }
    return true;
}

/**
 * Display admin notice if WooCommerce is not active
 */
function wc_bulk_price_editor_woocommerce_missing_notice()
{
    ?>
    <div class="error">
        <p><?php _e('WooCommerce Bulk Price Editor vyžaduje aktivní WooCommerce plugin.', 'woo-bulk-price-editor'); ?></p>
    </div>
    <?php
}

/**
 * Initialize the plugin
 */
function wc_bulk_price_editor_init()
{
    if (!wc_bulk_price_editor_check_woocommerce()) {
        return;
    }

    // Load plugin files
    require_once WC_BULK_PRICE_EDITOR_PLUGIN_DIR . 'includes/class-price-updater.php';
    require_once WC_BULK_PRICE_EDITOR_PLUGIN_DIR . 'admin/class-admin-page.php';

    // Initialize admin page
    if (is_admin()) {
        new WC_Bulk_Price_Editor_Admin();
    }
}
add_action('plugins_loaded', 'wc_bulk_price_editor_init');

/**
 * Plugin activation hook
 */
function wc_bulk_price_editor_activate()
{
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Tento plugin vyžaduje WooCommerce. Prosím nainstalujte a aktivujte WooCommerce nejdříve.', 'woo-bulk-price-editor'));
    }
}
register_activation_hook(__FILE__, 'wc_bulk_price_editor_activate');

/**
 * Add settings link on plugin page
 */
function wc_bulk_price_editor_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=wc-bulk-price-editor">' . __('Nastavení', 'woo-bulk-price-editor') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_bulk_price_editor_settings_link');
