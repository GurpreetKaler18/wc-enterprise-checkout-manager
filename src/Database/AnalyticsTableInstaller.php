<?php

declare(strict_types=1);

namespace SCE\Database;

final class AnalyticsTableInstaller
{
    public static function install(): void
    {
        global $wpdb;

        $tableName = self::tableName();
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            session_id VARCHAR(64) NOT NULL,
            customer_country VARCHAR(2) NOT NULL,
            cart_total DECIMAL(12,2) NOT NULL,
            item_count INT UNSIGNED NOT NULL,
            fee_applied TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY created_at (created_at)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'sce_checkout_analytics';
    }
}
