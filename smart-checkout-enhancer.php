<?php
/**
 * Plugin Name: Smart Checkout Enhancer
 * Description: Adds advanced checkout fee logic, dynamic cart pricing, analytics capture, secure logging, and background processing for WooCommerce.
 * Version: 1.0.0
 * Author: Checkout Engineering
 * Requires Plugins: woocommerce
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Autoloader.php';

\SCE\Autoloader::register();

add_action(
    'plugins_loaded',
    static function (): void {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $plugin = new \SCE\Plugin();
        $plugin->boot();
    }
);

register_activation_hook(
    __FILE__,
    static function (): void {
        \SCE\Database\AnalyticsTableInstaller::install();
    }
);
