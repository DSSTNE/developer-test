<?php

defined('ABSPATH') || exit;

class DTS_NP_Api
{
    private const API_URL = 'https://api.novaposhta.ua/v2.0/json/';
    private const CACHE_TTL = DAY_IN_SECONDS;

    public static function get_api_key(): string
    {
        $settings = get_option('dts_shipping_settings', []);
        return sanitize_text_field($settings['np_api_key'] ?? '');
    }

    public static function is_configured(): bool
    {
        return self::get_api_key() !== '';
    }

    /** @return array<int, array{ref: string, name: string}> */
    public static function get_cities(string $search = ''): array
    {
        $cache_key = 'dts_np_cities_' . md5(mb_strtolower($search));
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        if (!self::is_configured()) {
            return [];
        }

        $payload = [
            'apiKey' => self::get_api_key(),
            'modelName' => 'Address',
            'calledMethod' => 'getCities',
            'methodProperties' => [
                'FindByString' => sanitize_text_field($search),
                'Limit' => 50,
            ],
        ];

        $response = self::request($payload);
        if (!$response['success']) {
            DTS_Logger::log('NP getCities failed', $response);
            return [];
        }

        $cities = [];
        foreach ($response['data'] as $row) {
            $cities[] = [
                'ref' => sanitize_text_field($row['Ref'] ?? ''),
                'name' => sanitize_text_field($row['Description'] ?? ''),
            ];
        }

        set_transient($cache_key, $cities, self::CACHE_TTL);
        return $cities;
    }

    /** @return array<int, array{ref: string, name: string}> */
    public static function get_warehouses(string $city_ref): array
    {
        $city_ref = sanitize_text_field($city_ref);
        if ($city_ref === '') {
            return [];
        }

        $cache_key = 'dts_np_wh_' . md5($city_ref);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        if (!self::is_configured()) {
            return [];
        }

        $payload = [
            'apiKey' => self::get_api_key(),
            'modelName' => 'Address',
            'calledMethod' => 'getWarehouses',
            'methodProperties' => [
                'CityRef' => $city_ref,
                'Limit' => 200,
            ],
        ];

        $response = self::request($payload);
        if (!$response['success']) {
            DTS_Logger::log('NP getWarehouses failed', $response);
            return [];
        }

        $warehouses = [];
        foreach ($response['data'] as $row) {
            $warehouses[] = [
                'ref' => sanitize_text_field($row['Ref'] ?? ''),
                'name' => sanitize_text_field($row['Description'] ?? ''),
            ];
        }

        set_transient($cache_key, $warehouses, self::CACHE_TTL);
        return $warehouses;
    }

    /** @param array<string, mixed> $payload
     *  @return array{success: bool, data: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    private static function request(array $payload): array
    {
        $response = wp_remote_post(
            self::API_URL,
            [
                'timeout' => 20,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($payload),
            ]
        );

        if (is_wp_error($response)) {
            return ['success' => false, 'data' => [], 'errors' => [$response->get_error_message()]];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            return ['success' => false, 'data' => [], 'errors' => ['Invalid JSON response']];
        }

        return [
            'success' => !empty($body['success']),
            'data' => is_array($body['data'] ?? null) ? $body['data'] : [],
            'errors' => is_array($body['errors'] ?? null) ? $body['errors'] : [],
        ];
    }
}
