<?php

declare(strict_types=1);

namespace SCE\Checkout;

use SCE\Contracts\LoggerInterface;
use WC_Cart;

final class DynamicPriceService
{
    private const MIN_QUANTITY_FOR_DISCOUNT = 3;
    private const DISCOUNT_PERCENTAGE = 10;

    private bool $hasRun = false;

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function register(): void
    {
        add_action('woocommerce_before_calculate_totals', [$this, 'applyDynamicPricing'], 20);
    }

    public function applyDynamicPricing(WC_Cart $cart): void
    {
        if ($this->hasRun || (is_admin() && !defined('DOING_AJAX'))) {
            return;
        }

        $this->hasRun = true;

        foreach ($cart->get_cart() as $key => $cartItem) {
            $product = $cartItem['data'] ?? null;
            $quantity = (int) ($cartItem['quantity'] ?? 0);

            if (!$product || $quantity < self::MIN_QUANTITY_FOR_DISCOUNT) {
                continue;
            }

            $basePrice = (float) $product->get_regular_price();
            if ($basePrice <= 0) {
                $basePrice = (float) $product->get_price();
            }

            if ($basePrice <= 0) {
                continue;
            }

            $newPrice = round($basePrice * ((100 - self::DISCOUNT_PERCENTAGE) / 100), wc_get_price_decimals());
            $product->set_price($newPrice);

            $this->logger->info('dynamic_price_updated', [
                'cart_item_key' => $key,
                'product_id' => (int) $product->get_id(),
                'quantity' => $quantity,
                'base_price' => $basePrice,
                'new_price' => $newPrice,
            ]);
        }
    }
}
