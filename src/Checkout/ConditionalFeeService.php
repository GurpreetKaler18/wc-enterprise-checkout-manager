<?php

declare(strict_types=1);

namespace SCE\Checkout;

use SCE\Contracts\LoggerInterface;
use WC_Cart;

final class ConditionalFeeService
{
    private const DEFAULT_FEE_NAME = 'Smart Checkout Processing Fee';
    private const DEFAULT_FEE_AMOUNT = 15.00;
    private const DEFAULT_CART_THRESHOLD = 500.00;

    /** @var string[] */
    private array $eligibleCountries;

    private string $feeName;

    private float $feeAmount;

    private float $cartThreshold;

    public function __construct(private LoggerInterface $logger)
    {
        $this->eligibleCountries = $this->loadEligibleCountries();
        $this->feeName = $this->loadFeeName();
        $this->feeAmount = $this->loadFeeAmount();
        $this->cartThreshold = $this->loadCartThreshold();
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
        if ($cartTotal >= $this->cartThreshold) {
            return;
        }

        $cart->add_fee($this->feeName, $this->feeAmount, true, '');

        $this->logger->info('conditional_fee_applied', [
            'country' => $country,
            'cart_total' => $cartTotal,
            'fee_amount' => $this->feeAmount,
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

    /** @return string[] */
    private function loadEligibleCountries(): array
    {
        $defaultCountries = ['US', 'GB', 'CA'];

        $savedCountries = get_option('sce_eligible_countries', $defaultCountries);
        if (is_string($savedCountries)) {
            $savedCountries = explode(',', $savedCountries);
        }

        $normalized = [];
        foreach ((array) $savedCountries as $countryCode) {
            $countryCode = strtoupper(trim((string) $countryCode));
            if ($countryCode !== '') {
                $normalized[] = $countryCode;
            }
        }

        if ($normalized === []) {
            $normalized = $defaultCountries;
        }

        return (array) apply_filters('sce_eligible_countries', $normalized);
    }

    private function loadFeeName(): string
    {
        $savedName = get_option('sce_fee_name', self::DEFAULT_FEE_NAME);

        if (!is_string($savedName) || trim($savedName) === '') {
            return self::DEFAULT_FEE_NAME;
        }

        return sanitize_text_field($savedName);
    }

    private function loadFeeAmount(): float
    {
        $savedAmount = get_option('sce_fee_amount', self::DEFAULT_FEE_AMOUNT);
        $feeAmount = is_numeric($savedAmount) ? (float) $savedAmount : self::DEFAULT_FEE_AMOUNT;

        return max(0.0, $feeAmount);
    }

    private function loadCartThreshold(): float
    {
        $savedThreshold = get_option('sce_cart_threshold', self::DEFAULT_CART_THRESHOLD);
        $threshold = is_numeric($savedThreshold) ? (float) $savedThreshold : self::DEFAULT_CART_THRESHOLD;

        return max(0.0, $threshold);
    }
}
