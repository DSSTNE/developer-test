<?php

defined('ABSPATH') || exit;

class DTS_Checkout
{
    public const METHOD_ID = 'dts_custom_shipping';

    public static function init(): void
    {
        add_action('woocommerce_after_shipping_rate', [self::class, 'render_fields'], 10, 2);
        add_action('woocommerce_checkout_process', [self::class, 'validate']);
        add_action('woocommerce_checkout_create_order', [self::class, 'save_to_order'], 10, 2);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_scripts']);
    }

    public static function is_method_selected(): bool
    {
        $chosen = WC()->session ? WC()->session->get('chosen_shipping_methods') : [];
        if (!is_array($chosen) || empty($chosen[0])) {
            return false;
        }

        return str_starts_with((string) $chosen[0], self::METHOD_ID);
    }

    public static function render_fields(WC_Shipping_Rate $rate, int $index): void
    {
        if (!str_starts_with($rate->get_id(), self::METHOD_ID)) {
            return;
        }

        $use_np = DTS_NP_Api::is_configured();
        $city = WC()->checkout()->get_value('dts_delivery_city');
        $city_ref = WC()->checkout()->get_value('dts_delivery_city_ref');
        $branch = WC()->checkout()->get_value('dts_delivery_branch');
        $branch_ref = WC()->checkout()->get_value('dts_delivery_branch_ref');
        $comment = WC()->checkout()->get_value('dts_delivery_comment');

        echo '<div class="dts-delivery-fields" data-np="' . esc_attr($use_np ? '1' : '0') . '">';

        if ($use_np) {
            woocommerce_form_field('dts_delivery_city_ref', [
                'type' => 'select',
                'class' => ['form-row-wide'],
                'label' => __('City', 'dts-custom-shipping'),
                'required' => true,
                'options' => self::city_options($city_ref, $city),
            ], $city_ref);

            echo '<input type="hidden" name="dts_delivery_city" id="dts_delivery_city" value="' . esc_attr($city) . '" />';

            woocommerce_form_field('dts_delivery_branch_ref', [
                'type' => 'select',
                'class' => ['form-row-wide'],
                'label' => __('Branch', 'dts-custom-shipping'),
                'required' => true,
                'options' => self::branch_options($city_ref, $branch_ref, $branch),
            ], $branch_ref);

            echo '<input type="hidden" name="dts_delivery_branch" id="dts_delivery_branch" value="' . esc_attr($branch) . '" />';
        } else {
            woocommerce_form_field('dts_delivery_city', [
                'type' => 'text',
                'class' => ['form-row-wide'],
                'label' => __('City', 'dts-custom-shipping'),
                'required' => true,
                'placeholder' => __('Kyiv', 'dts-custom-shipping'),
            ], $city);

            woocommerce_form_field('dts_delivery_branch', [
                'type' => 'text',
                'class' => ['form-row-wide'],
                'label' => __('Branch', 'dts-custom-shipping'),
                'required' => true,
                'placeholder' => __('Branch #1', 'dts-custom-shipping'),
            ], $branch);
        }

        woocommerce_form_field('dts_delivery_comment', [
            'type' => 'textarea',
            'class' => ['form-row-wide'],
            'label' => __('Comment', 'dts-custom-shipping'),
            'required' => false,
            'placeholder' => __('Delivery notes', 'dts-custom-shipping'),
        ], $comment);

        echo '</div>';
    }

    /** @return array<string, string> */
    private static function city_options(string $selected_ref, string $selected_name): array
    {
        $options = ['' => __('Select city', 'dts-custom-shipping')];
        foreach (DTS_NP_Api::get_cities('') as $city) {
            $options[$city['ref']] = $city['name'];
        }
        if ($selected_ref && !isset($options[$selected_ref]) && $selected_name) {
            $options[$selected_ref] = $selected_name;
        }
        return $options;
    }

    /** @return array<string, string> */
    private static function branch_options(string $city_ref, string $selected_ref, string $selected_name): array
    {
        $options = ['' => __('Select branch', 'dts-custom-shipping')];
        if ($city_ref) {
            foreach (DTS_NP_Api::get_warehouses($city_ref) as $wh) {
                $options[$wh['ref']] = $wh['name'];
            }
        }
        if ($selected_ref && !isset($options[$selected_ref]) && $selected_name) {
            $options[$selected_ref] = $selected_name;
        }
        return $options;
    }

    public static function validate(): void
    {
        if (!self::is_method_selected()) {
            return;
        }

        $city = self::read_city();
        $branch = self::read_branch();
        $comment = sanitize_textarea_field(wp_unslash($_POST['dts_delivery_comment'] ?? ''));

        if ($city === '') {
            wc_add_notice(__('Please enter delivery city.', 'dts-custom-shipping'), 'error');
        } elseif (mb_strlen($city) < 2 || mb_strlen($city) > 100) {
            wc_add_notice(__('City must be 2–100 characters.', 'dts-custom-shipping'), 'error');
        }

        if ($branch === '') {
            wc_add_notice(__('Please enter delivery branch.', 'dts-custom-shipping'), 'error');
        } elseif (mb_strlen($branch) < 2 || mb_strlen($branch) > 150) {
            wc_add_notice(__('Branch must be 2–150 characters.', 'dts-custom-shipping'), 'error');
        }

        if (mb_strlen($comment) > 500) {
            wc_add_notice(__('Comment is too long (max 500).', 'dts-custom-shipping'), 'error');
        }
    }

    public static function save_to_order(WC_Order $order, array $data): void
    {
        if (!self::is_method_selected()) {
            return;
        }

        $city = self::read_city();
        $branch = self::read_branch();
        $comment = sanitize_textarea_field(wp_unslash($_POST['dts_delivery_comment'] ?? ''));

        $order->update_meta_data('_dts_delivery_city', $city);
        $order->update_meta_data('_dts_delivery_branch', $branch);
        $order->update_meta_data('_dts_delivery_comment', $comment);

        if (DTS_NP_Api::is_configured()) {
            $order->update_meta_data('_dts_delivery_city_ref', sanitize_text_field(wp_unslash($_POST['dts_delivery_city_ref'] ?? '')));
            $order->update_meta_data('_dts_delivery_branch_ref', sanitize_text_field(wp_unslash($_POST['dts_delivery_branch_ref'] ?? '')));
        }

        DTS_Logger::log('Delivery data saved to order', [
            'order_id' => $order->get_id(),
            'city' => $city,
            'branch' => $branch,
        ]);
    }

    public static function show_in_admin(WC_Order $order): void
    {
        $city = $order->get_meta('_dts_delivery_city');
        if ($city === '') {
            return;
        }

        echo '<p><strong>' . esc_html__('Delivery city', 'dts-custom-shipping') . ':</strong> ';
        echo esc_html($city) . '</p>';
        echo '<p><strong>' . esc_html__('Delivery branch', 'dts-custom-shipping') . ':</strong> ';
        echo esc_html((string) $order->get_meta('_dts_delivery_branch')) . '</p>';

        $comment = (string) $order->get_meta('_dts_delivery_comment');
        if ($comment !== '') {
            echo '<p><strong>' . esc_html__('Delivery comment', 'dts-custom-shipping') . ':</strong> ';
            echo esc_html($comment) . '</p>';
        }
    }

    public static function enqueue_scripts(): void
    {
        if (!is_checkout()) {
            return;
        }

        wp_enqueue_script(
            'dts-shipping-checkout',
            DTS_SHIPPING_URL . 'assets/js/checkout.js',
            ['jquery'],
            DTS_SHIPPING_VERSION,
            true
        );

        wp_enqueue_style(
            'dts-shipping-checkout',
            DTS_SHIPPING_URL . 'assets/css/checkout.css',
            [],
            DTS_SHIPPING_VERSION
        );

        wp_localize_script('dts-shipping-checkout', 'dtsShipping', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dts_checkout'),
            'selectCity' => __('Select city', 'dts-custom-shipping'),
            'selectBranch' => __('Select branch', 'dts-custom-shipping'),
        ]);
    }

    private static function read_city(): string
    {
        if (DTS_NP_Api::is_configured()) {
            $ref = sanitize_text_field(wp_unslash($_POST['dts_delivery_city_ref'] ?? ''));
            $name = sanitize_text_field(wp_unslash($_POST['dts_delivery_city'] ?? ''));
            if ($ref === '') {
                return '';
            }
            if ($name !== '') {
                return $name;
            }
            foreach (DTS_NP_Api::get_cities('') as $city) {
                if ($city['ref'] === $ref) {
                    return $city['name'];
                }
            }
            return '';
        }

        return sanitize_text_field(wp_unslash($_POST['dts_delivery_city'] ?? ''));
    }

    private static function read_branch(): string
    {
        if (DTS_NP_Api::is_configured()) {
            $city_ref = sanitize_text_field(wp_unslash($_POST['dts_delivery_city_ref'] ?? ''));
            $ref = sanitize_text_field(wp_unslash($_POST['dts_delivery_branch_ref'] ?? ''));
            $name = sanitize_text_field(wp_unslash($_POST['dts_delivery_branch'] ?? ''));
            if ($ref === '') {
                return '';
            }
            if ($name !== '') {
                return $name;
            }
            foreach (DTS_NP_Api::get_warehouses($city_ref) as $wh) {
                if ($wh['ref'] === $ref) {
                    return $wh['name'];
                }
            }
            return '';
        }

        return sanitize_text_field(wp_unslash($_POST['dts_delivery_branch'] ?? ''));
    }
}
