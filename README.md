# Smart Checkout Enhancer (WooCommerce Plugin)

Smart Checkout Enhancer is a production-oriented WooCommerce extension that provides:

1. **Conditional checkout fee logic**
2. **Dynamic cart item pricing updates**
3. **Checkout analytics persistence**
4. **Post-order background processing**
5. **Secure checkout event logging**

## Features

### 1) Conditional Checkout Fee
The plugin applies a processing fee only when **all** conditions are met:
- Cart contains a subscription product (`subscription` or `variable-subscription` product type)
- Customer country is in an eligible country list (default: `US`, `GB`, `CA`)
- Cart subtotal is below `500`

Defaults:
- Fee label: `Smart Checkout Processing Fee`
- Fee amount: `15.00`

### 2) Dynamic Cart Item Pricing
The plugin dynamically adjusts item pricing before totals are calculated:
- Applies a configurable discount percentage when cart line quantity meets a configurable minimum (defaults: 10% at quantity 3)
- Uses regular price as base when available
- Logs every modified line item for observability

### 3) Checkout Analytics Storage
At checkout/order processing time, the plugin stores analytics in a dedicated DB table:
- `order_id`
- `session_id`
- `customer_country`
- `cart_total`
- `item_count`
- `fee_applied`
- `created_at`

Table name:
- `{$wpdb->prefix}sce_checkout_analytics`

### 4) Background Job After Order Completion
When an order reaches `completed` status:
- A WP-Cron single event is scheduled (`+1 minute`)
- The job updates order metadata and logs completion

### 5) Secure Event Logging
Important lifecycle events are logged through WooCommerce logger with:
- Sanitized keys and values
- Structured JSON payloads
- Dedicated source: `smart-checkout-enhancer`

---

## Architecture (OOP + SOLID)

The plugin follows a service-based design and dependency injection:

- `SCE\Plugin`: Composition root that wires services.
- `SCE\Checkout\ConditionalFeeService`: Single responsibility for fee logic.
- `SCE\Checkout\DynamicPriceService`: Single responsibility for dynamic pricing.
- `SCE\Analytics\CheckoutAnalyticsRecorder`: Responsible for analytics persistence.
- `SCE\Background\OrderCompletionJob`: Responsible for async/background order processing.
- `SCE\Logging\EventLogger`: Logging implementation behind `LoggerInterface`.
- `SCE\Database\AnalyticsTableInstaller`: Database installation concern.

SOLID highlights:
- **S**: Each class handles one bounded concern.
- **O**: Country eligibility is filterable via `sce_eligible_countries`.
- **L**: Services depend on abstractions (`LoggerInterface`).
- **I**: Logging contract is intentionally minimal and focused.
- **D**: High-level services are not coupled to low-level logger internals.

---

## Installation

1. Copy this project folder into your WordPress plugins directory:
   - `wp-content/plugins/smart-checkout-enhancer` (recommended folder name)
2. Ensure WooCommerce is active.
3. Activate **Smart Checkout Enhancer** from WP Admin → Plugins.
4. On activation, analytics table is created automatically.

## Configuration Notes

### Settings Page
Use **WooCommerce → Smart Checkout** to manage dynamic fee fields without code changes:
- `eligible_countries`
- `FEE_NAME`
- `FEE_AMOUNT`
- `CART_THRESHOLD`
- `MIN_QUANTITY_FOR_DISCOUNT`
- `DISCOUNT_PERCENTAGE`

### Eligible Countries Filter (Optional)
You can still override eligible countries programmatically via filter:

```php
add_filter('sce_eligible_countries', function (array $countries): array {
    return ['US', 'DE', 'FR'];
});
```

---

## Operational Notes

- Background processing uses WP-Cron. Ensure cron is working in production.
- Logs are available in WooCommerce → Status → Logs.
- Subscription detection expects WooCommerce Subscriptions product types.

---

## AI Tooling Disclosure

AI assistance was used to:
- Draft initial class/module decomposition.
- Refine hook selection and data-flow boundaries.
- Improve README clarity and operational guidance.

Manual engineering review was applied to:
- Align implementation with WooCommerce hooks and plugin lifecycle.
- Tighten sanitization and logging behavior.
- Ensure the final structure follows OOP and SOLID principles.

