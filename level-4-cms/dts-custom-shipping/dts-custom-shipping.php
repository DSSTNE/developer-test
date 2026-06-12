<?php
/**
 * Plugin Name: DTS Custom Delivery
 * Description: Custom WooCommerce shipping method with city, branch and comment fields.
 * Version: 1.0.0
 * Author: Denys
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * Text Domain: dts-custom-shipping
 */

defined('ABSPATH') || exit;

define('DTS_SHIPPING_VERSION', '1.0.0');
define('DTS_SHIPPING_PATH', plugin_dir_path(__FILE__));
define('DTS_SHIPPING_URL', plugin_dir_url(__FILE__));

require_once DTS_SHIPPING_PATH . 'includes/class-dts-logger.php';
require_once DTS_SHIPPING_PATH . 'includes/class-dts-np-api.php';
require_once DTS_SHIPPING_PATH . 'includes/class-dts-admin.php';

final class DTS_Custom_Shipping_Plugin
{
    public static function init(): void
    {
        add_action('plugins_loaded', [self::class, 'on_plugins_loaded']);
        add_action('before_woocommerce_init', [self::class, 'declare_hpos_compat']);
    }

    public static function on_plugins_loaded(): void
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [self::class, 'woocommerce_missing_notice']);
            return;
        }

        add_action('woocommerce_shipping_init', [self::class, 'load_shipping_method']);
        add_filter('woocommerce_shipping_methods', [self::class, 'register_shipping_method']);

        require_once DTS_SHIPPING_PATH . 'includes/class-dts-checkout.php';
        require_once DTS_SHIPPING_PATH . 'includes/class-dts-webhook.php';
        require_once DTS_SHIPPING_PATH . 'includes/class-dts-blocks-checkout.php';

        DTS_Admin::init();
        DTS_Checkout::init();
        DTS_Webhook::init();
        DTS_Blocks_Checkout::init();
    }

    public static function load_shipping_method(): void
    {
        require_once DTS_SHIPPING_PATH . 'includes/class-dts-shipping-method.php';
    }

    public static function declare_hpos_compat(): void
    {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }

    /** @param array<string, string> $methods */
    public static function register_shipping_method(array $methods): array
    {
        $methods['dts_custom_shipping'] = 'DTS_Shipping_Method';
        return $methods;
    }

    public static function woocommerce_missing_notice(): void
    {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('DTS Custom Delivery requires WooCommerce.', 'dts-custom-shipping');
        echo '</p></div>';
    }
}

DTS_Custom_Shipping_Plugin::init();

register_activation_hook(__FILE__, static function (): void {
    if (!get_option('dts_shipping_settings')) {
        update_option('dts_shipping_settings', [
            'enable_logging' => 'yes',
            'np_api_key' => '',
            'webhook_url' => '',
            'webhook_secret' => wp_generate_password(32, false),
        ]);
    }
});
