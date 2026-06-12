<?php

defined('ABSPATH') || exit;

class DTS_Blocks_Checkout
{
    public static function init(): void
    {
        add_action('woocommerce_init', [self::class, 'register_fields']);
        add_action(
            'woocommerce_blocks_validate_additional_checkout_field',
            [self::class, 'validate_field'],
            10,
            3
        );
        add_action(
            'woocommerce_store_api_checkout_order_processed',
            [self::class, 'save_from_order'],
            10,
            1
        );
    }

    public static function register_fields(): void
    {
        if (!function_exists('woocommerce_register_additional_checkout_field')) {
            return;
        }

        woocommerce_register_additional_checkout_field([
            'id' => 'dts/delivery-city',
            'label' => __('Delivery city', 'dts-custom-shipping'),
            'location' => 'order',
            'type' => 'text',
            'required' => false,
        ]);

        woocommerce_register_additional_checkout_field([
            'id' => 'dts/delivery-branch',
            'label' => __('Delivery branch', 'dts-custom-shipping'),
            'location' => 'order',
            'type' => 'text',
            'required' => false,
        ]);

        woocommerce_register_additional_checkout_field([
            'id' => 'dts/delivery-comment',
            'label' => __('Delivery comment', 'dts-custom-shipping'),
            'location' => 'order',
            'type' => 'text',
            'required' => false,
        ]);
    }

    /** @param \WP_Error $errors */
    public static function validate_field($errors, string $field_key, $field_value): void
    {
        if (!self::is_dts_shipping_selected()) {
            return;
        }

        $value = is_string($field_value) ? trim($field_value) : '';

        if ($field_key === 'dts/delivery-city') {
            if ($value === '') {
                $errors->add('dts_city', __('Please enter delivery city.', 'dts-custom-shipping'));
            } elseif (mb_strlen($value) < 2 || mb_strlen($value) > 100) {
                $errors->add('dts_city', __('City must be 2–100 characters.', 'dts-custom-shipping'));
            }
        }

        if ($field_key === 'dts/delivery-branch') {
            if ($value === '') {
                $errors->add('dts_branch', __('Please enter delivery branch.', 'dts-custom-shipping'));
            } elseif (mb_strlen($value) < 2 || mb_strlen($value) > 150) {
                $errors->add('dts_branch', __('Branch must be 2–150 characters.', 'dts-custom-shipping'));
            }
        }

        if ($field_key === 'dts/delivery-comment' && mb_strlen($value) > 500) {
            $errors->add('dts_comment', __('Comment is too long (max 500).', 'dts-custom-shipping'));
        }
    }

    public static function save_from_order(WC_Order $order): void
    {
        if (!self::order_uses_dts_shipping($order)) {
            return;
        }

        $city = self::read_additional_field($order, 'dts/delivery-city');
        $branch = self::read_additional_field($order, 'dts/delivery-branch');
        $comment = self::read_additional_field($order, 'dts/delivery-comment');

        if ($city !== '') {
            $order->update_meta_data('_dts_delivery_city', $city);
        }
        if ($branch !== '') {
            $order->update_meta_data('_dts_delivery_branch', $branch);
        }
        if ($comment !== '') {
            $order->update_meta_data('_dts_delivery_comment', $comment);
        }

        $order->save();

        DTS_Logger::log('Block checkout delivery saved', ['order_id' => $order->get_id()]);
    }

    private static function is_dts_shipping_selected(): bool
    {
        if (!WC()->session) {
            return false;
        }

        $chosen = WC()->session->get('chosen_shipping_methods');
        if (!is_array($chosen) || empty($chosen[0])) {
            return false;
        }

        return str_starts_with((string) $chosen[0], DTS_Checkout::METHOD_ID);
    }

    private static function order_uses_dts_shipping(WC_Order $order): bool
    {
        foreach ($order->get_shipping_methods() as $method) {
            if (str_starts_with($method->get_method_id(), DTS_Checkout::METHOD_ID)) {
                return true;
            }
        }
        return false;
    }

    private static function read_additional_field(WC_Order $order, string $field_id): string
    {
        if (class_exists('\Automattic\WooCommerce\Blocks\Package')) {
            $checkout_fields = \Automattic\WooCommerce\Blocks\Package::container()->get(
                \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class
            );
            if ($checkout_fields && method_exists($checkout_fields, 'get_field_from_object')) {
                $value = $checkout_fields->get_field_from_object($field_id, $order, 'other');
                if (is_string($value) && $value !== '') {
                    return sanitize_text_field($value);
                }
            }
        }

        $meta_key = '_wc_other/' . $field_id;
        return sanitize_text_field((string) $order->get_meta($meta_key));
    }
}
