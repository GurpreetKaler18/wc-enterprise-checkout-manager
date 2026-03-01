<?php

declare(strict_types=1);

namespace SCE\Analytics;

use SCE\Contracts\LoggerInterface;
use SCE\Database\AnalyticsTableInstaller;
use WC_Order;

final class CheckoutAnalyticsRecorder
{
    private const FEE_LABEL = 'Smart Checkout Processing Fee';

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function register(): void
    {
        add_action('woocommerce_checkout_create_order', [$this, 'persistOrderMeta'], 20, 2);
        add_action('woocommerce_checkout_order_processed', [$this, 'persistAnalyticsRow'], 20, 3);
    }

    public function persistOrderMeta(WC_Order $order, array $postedData = []): void
    {
        $cart = WC()->cart;
        if (!$cart) {
            return;
        }

        $order->update_meta_data('_sce_cart_items_count', $cart->get_cart_contents_count());
        $order->update_meta_data('_sce_cart_total', (float) $cart->get_subtotal());
        $order->update_meta_data('_sce_fee_applied', $this->isFeeApplied($cart) ? '1' : '0');

        $this->logger->info('checkout_order_meta_stored', [
            'order_id' => (int) $order->get_id(),
            'cart_items' => (int) $cart->get_cart_contents_count(),
        ]);
    }

    public function persistAnalyticsRow(int $orderId, array $postedData = [], ?\WC_Order $order = null): void
    {
        $order = $order ?: wc_get_order($orderId);
        if (!$order) {
            return;
        }

        global $wpdb;

        $inserted = $wpdb->insert(
            AnalyticsTableInstaller::tableName(),
            [
                'order_id' => $orderId,
                'session_id' => $this->resolveSessionId(),
                'customer_country' => strtoupper((string) $order->get_billing_country()),
                'cart_total' => (float) $order->get_meta('_sce_cart_total', true),
                'item_count' => (int) $order->get_meta('_sce_cart_items_count', true),
                'fee_applied' => (int) $order->get_meta('_sce_fee_applied', true),
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%s', '%s', '%f', '%d', '%d', '%s']
        );

        if ($inserted === false) {
            $this->logger->error('analytics_insert_failed', ['order_id' => $orderId]);

            return;
        }

        $this->logger->info('analytics_row_inserted', ['order_id' => $orderId]);
    }

    private function resolveSessionId(): string
    {
        if (WC()->session && method_exists(WC()->session, 'get_customer_id')) {
            return sanitize_text_field((string) WC()->session->get_customer_id());
        }

        return 'guest';
    }

    private function isFeeApplied(\WC_Cart $cart): bool
    {
        foreach ($cart->get_fees() as $fee) {
            if (isset($fee->name) && $fee->name === self::FEE_LABEL) {
                return true;
            }
        }

        return false;
    }
}
