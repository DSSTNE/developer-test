<?php

defined('ABSPATH') || exit;

class DTS_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('wp_ajax_dts_np_cities', [self::class, 'ajax_cities']);
        add_action('wp_ajax_nopriv_dts_np_cities', [self::class, 'ajax_cities']);
        add_action('wp_ajax_dts_np_warehouses', [self::class, 'ajax_warehouses']);
        add_action('wp_ajax_nopriv_dts_np_warehouses', [self::class, 'ajax_warehouses']);
    }

    public static function add_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('DTS Delivery', 'dts-custom-shipping'),
            __('DTS Delivery', 'dts-custom-shipping'),
            'manage_woocommerce',
            'dts-delivery-settings',
            [self::class, 'render_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting(
            'dts_shipping_settings_group',
            'dts_shipping_settings',
            [self::class, 'sanitize_settings']
        );
    }

    /** @param array<string, mixed> $input */
    public static function sanitize_settings(array $input): array
    {
        return [
            'enable_logging' => isset($input['enable_logging']) ? 'yes' : 'no',
            'np_api_key' => sanitize_text_field($input['np_api_key'] ?? ''),
            'webhook_url' => esc_url_raw($input['webhook_url'] ?? ''),
            'webhook_secret' => sanitize_text_field($input['webhook_secret'] ?? ''),
        ];
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = get_option('dts_shipping_settings', []);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('DTS Delivery settings', 'dts-custom-shipping'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('dts_shipping_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Logging', 'dts-custom-shipping'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="dts_shipping_settings[enable_logging]" value="yes"
                                    <?php checked(($settings['enable_logging'] ?? 'yes'), 'yes'); ?> />
                                <?php esc_html_e('Write errors to uploads/dts-shipping.log', 'dts-custom-shipping'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Nova Poshta API key', 'dts-custom-shipping'); ?></th>
                        <td>
                            <input type="text" class="regular-text"
                                name="dts_shipping_settings[np_api_key]"
                                value="<?php echo esc_attr($settings['np_api_key'] ?? ''); ?>" />
                            <p class="description">
                                <?php esc_html_e('Optional. Enables city/warehouse dropdowns with cache.', 'dts-custom-shipping'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Webhook URL', 'dts-custom-shipping'); ?></th>
                        <td>
                            <input type="url" class="regular-text"
                                name="dts_shipping_settings[webhook_url]"
                                value="<?php echo esc_attr($settings['webhook_url'] ?? ''); ?>" />
                            <p class="description">
                                <?php esc_html_e('POST on new order with delivery data.', 'dts-custom-shipping'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Webhook secret', 'dts-custom-shipping'); ?></th>
                        <td>
                            <input type="text" class="regular-text" readonly
                                value="<?php echo esc_attr($settings['webhook_secret'] ?? ''); ?>" />
                            <input type="hidden" name="dts_shipping_settings[webhook_secret]"
                                value="<?php echo esc_attr($settings['webhook_secret'] ?? ''); ?>" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_dts-delivery-settings') {
            return;
        }
        wp_enqueue_style(
            'dts-shipping-admin',
            DTS_SHIPPING_URL . 'assets/css/admin.css',
            [],
            DTS_SHIPPING_VERSION
        );
    }

    public static function ajax_cities(): void
    {
        check_ajax_referer('dts_checkout', 'nonce');

        $search = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        wp_send_json_success(DTS_NP_Api::get_cities($search));
    }

    public static function ajax_warehouses(): void
    {
        check_ajax_referer('dts_checkout', 'nonce');

        $city_ref = sanitize_text_field(wp_unslash($_GET['city_ref'] ?? ''));
        wp_send_json_success(DTS_NP_Api::get_warehouses($city_ref));
    }
}
