<?php

defined('ABSPATH') || exit;

class DTS_Logger
{
    private const LOG_FILE = 'dts-shipping.log';

    public static function enabled(): bool
    {
        $settings = get_option('dts_shipping_settings', []);
        return ($settings['enable_logging'] ?? 'yes') === 'yes';
    }

    public static function log(string $message, array $context = []): void
    {
        if (!self::enabled()) {
            return;
        }

        $upload = wp_upload_dir();
        if (!empty($upload['error'])) {
            return;
        }

        $line = sprintf(
            "[%s] %s %s\n",
            gmdate('Y-m-d H:i:s'),
            $message,
            $context ? wp_json_encode($context) : ''
        );

        $path = trailingslashit($upload['basedir']) . self::LOG_FILE;
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
