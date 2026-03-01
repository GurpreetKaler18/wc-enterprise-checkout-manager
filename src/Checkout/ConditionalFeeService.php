<?php

declare(strict_types=1);

namespace SCE\Checkout;

use SCE\Contracts\LoggerInterface;
use WC_Cart;

final class ConditionalFeeService
{
    private const FEE_NAME = 'Smart Checkout Processing Fee';
    private const FEE_AMOUNT = 15.00;
    private const CART_THRESHOLD = 500.00;

    /** @var string[] */
    private array $eligibleCountries;

    public function __construct(private LoggerInterface $logger)
    {
        $this->eligibleCountries = (array) apply_filters(
            'sce_eligible_countries',
            ['US', 'GB', 'CA']
        );
    }

    public function register(): void
    {
        add_action('woocommerce_cart_calculate_fees', [$this, 'addConditionalFee']);
    }

    public function addConditionalFee(WC_Cart $cart): void
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!$this->containsSubscriptionProduct($cart)) {
            return;
        }

        $country = $this->resolveCustomerCountry();
        if (!in_array($country, $this->eligibleCountries, true)) {
            return;
        }

        $cartTotal = (float) $cart->get_subtotal();
        if ($cartTotal >= self::CART_THRESHOLD) {
            return;
        }

        $cart->add_fee(self::FEE_NAME, self::FEE_AMOUNT, true, '');

        $this->logger->info('conditional_fee_applied', [
            'country' => $country,
            'cart_total' => $cartTotal,
            'fee_amount' => self::FEE_AMOUNT,
        ]);
    }

    private function containsSubscriptionProduct(WC_Cart $cart): bool
    {
        foreach ($cart->get_cart() as $cartItem) {
            $product = $cartItem['data'] ?? null;

            if (!$product || !method_exists($product, 'is_type')) {
                continue;
            }

            if ($product->is_type('subscription') || $product->is_type('variable-subscription')) {
                return true;
            }
        }

        return false;
    }

    private function resolveCustomerCountry(): string
    {
        if (!WC()->customer) {
            return '';
        }

        $country = WC()->customer->get_shipping_country();
        if ($country === '') {
            $country = WC()->customer->get_billing_country();
        }

        return strtoupper((string) $country);
    }
}
