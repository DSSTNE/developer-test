<?php

defined('ABSPATH') || exit;

class DTS_Webhook
{
    public static function init(): void
    {
        add_action('woocommerce_checkout_order_processed', [self::class, 'send'], 10, 3);
    }

    public static function send(int $order_id, array $posted_data, WC_Order $order): void
    {
        $settings = get_option('dts_shipping_settings', []);
        $url = esc_url_raw($settings['webhook_url'] ?? '');

        if ($url === '') {
            return;
        }

        $city = (string) $order->get_meta('_dts_delivery_city');
        if ($city === '') {
            return;
        }

        $payload = [
            'event' => 'order.created',
            'order_id' => $order_id,
            'delivery' => [
                'city' => $city,
                'branch' => (string) $order->get_meta('_dts_delivery_branch'),
                'comment' => (string) $order->get_meta('_dts_delivery_comment'),
            ],
            'customer' => [
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ],
        ];

        $secret = sanitize_text_field($settings['webhook_secret'] ?? '');
        $body = wp_json_encode($payload);

        $response = wp_remote_post(
            $url,
            [
                'timeout' => 15,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-DTS-Signature' => hash_hmac('sha256', (string) $body, $secret),
                ],
                'body' => $body,
            ]
        );

        if (is_wp_error($response)) {
            DTS_Logger::log('Webhook failed', ['order_id' => $order_id, 'error' => $response->get_error_message()]);
            return;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            DTS_Logger::log('Webhook bad status', [
                'order_id' => $order_id,
                'status' => $code,
                'body' => wp_remote_retrieve_body($response),
            ]);
        }
    }
}
