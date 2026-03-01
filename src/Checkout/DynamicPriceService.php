<?php

declare(strict_types=1);

namespace SCE\Checkout;

use SCE\Contracts\LoggerInterface;
use WC_Cart;

final class DynamicPriceService
{
    private const DEFAULT_MIN_QUANTITY_FOR_DISCOUNT = 3;
    private const DEFAULT_DISCOUNT_PERCENTAGE = 10.0;

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
        $minQuantityForDiscount = $this->getMinQuantityForDiscount();
        $discountPercentage = $this->getDiscountPercentage();

        foreach ($cart->get_cart() as $key => $cartItem) {
            $product = $cartItem['data'] ?? null;
            $quantity = (int) ($cartItem['quantity'] ?? 0);

            if (!$product || $quantity < $minQuantityForDiscount) {
                continue;
            }

            $basePrice = (float) $product->get_regular_price();
            if ($basePrice <= 0) {
                $basePrice = (float) $product->get_price();
            }

            if ($basePrice <= 0) {
                continue;
            }

            $newPrice = round($basePrice * ((100 - $discountPercentage) / 100), wc_get_price_decimals());
            $product->set_price($newPrice);

            $this->logger->info('dynamic_price_updated', [
                'cart_item_key' => $key,
                'product_id' => (int) $product->get_id(),
                'quantity' => $quantity,
                'min_quantity_for_discount' => $minQuantityForDiscount,
                'discount_percentage' => $discountPercentage,
                'base_price' => $basePrice,
                'new_price' => $newPrice,
            ]);
        }
    }

    private function getMinQuantityForDiscount(): int
    {
        $savedValue = get_option('sce_min_quantity_for_discount', self::DEFAULT_MIN_QUANTITY_FOR_DISCOUNT);

        if (!is_numeric($savedValue)) {
            return self::DEFAULT_MIN_QUANTITY_FOR_DISCOUNT;
        }

        return max(1, (int) $savedValue);
    }

    private function getDiscountPercentage(): float
    {
        $savedValue = get_option('sce_discount_percentage', self::DEFAULT_DISCOUNT_PERCENTAGE);

        if (!is_numeric($savedValue)) {
            return self::DEFAULT_DISCOUNT_PERCENTAGE;
        }

        return min(100.0, max(0.0, (float) $savedValue));
    }
}
