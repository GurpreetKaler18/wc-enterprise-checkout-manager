<?php

declare(strict_types=1);

namespace SCE\Admin;

final class SettingsPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Smart Checkout Settings', 'smart-checkout-enhancer'),
            __('Smart Checkout', 'smart-checkout-enhancer'),
            'manage_woocommerce',
            'sce-settings',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting('sce_settings_group', 'sce_eligible_countries', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitizeCountries'],
            'default' => 'US,GB,CA',
        ]);

        register_setting('sce_settings_group', 'sce_fee_name', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Smart Checkout Processing Fee',
        ]);

        register_setting('sce_settings_group', 'sce_fee_amount', [
            'type' => 'number',
            'sanitize_callback' => [$this, 'sanitizeNumber'],
            'default' => 15,
        ]);

        register_setting('sce_settings_group', 'sce_cart_threshold', [
            'type' => 'number',
            'sanitize_callback' => [$this, 'sanitizeNumber'],
            'default' => 500,
        ]);
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Smart Checkout Settings', 'smart-checkout-enhancer'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('sce_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="sce_eligible_countries"><?php esc_html_e('eligible_countries', 'smart-checkout-enhancer'); ?></label></th>
                        <td>
                            <input
                                name="sce_eligible_countries"
                                id="sce_eligible_countries"
                                type="text"
                                class="regular-text"
                                value="<?php echo esc_attr((string) get_option('sce_eligible_countries', 'US,GB,CA')); ?>"
                            />
                            <p class="description"><?php esc_html_e('Comma-separated ISO country codes (example: US,GB,CA).', 'smart-checkout-enhancer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sce_fee_name"><?php esc_html_e('FEE_NAME', 'smart-checkout-enhancer'); ?></label></th>
                        <td>
                            <input
                                name="sce_fee_name"
                                id="sce_fee_name"
                                type="text"
                                class="regular-text"
                                value="<?php echo esc_attr((string) get_option('sce_fee_name', 'Smart Checkout Processing Fee')); ?>"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sce_fee_amount"><?php esc_html_e('FEE_AMOUNT', 'smart-checkout-enhancer'); ?></label></th>
                        <td>
                            <input
                                name="sce_fee_amount"
                                id="sce_fee_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                value="<?php echo esc_attr((string) get_option('sce_fee_amount', '15')); ?>"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sce_cart_threshold"><?php esc_html_e('CART_THRESHOLD', 'smart-checkout-enhancer'); ?></label></th>
                        <td>
                            <input
                                name="sce_cart_threshold"
                                id="sce_cart_threshold"
                                type="number"
                                step="0.01"
                                min="0"
                                value="<?php echo esc_attr((string) get_option('sce_cart_threshold', '500')); ?>"
                            />
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button(__('Save Settings', 'smart-checkout-enhancer')); ?>
            </form>
        </div>
        <?php
    }

    public function sanitizeCountries(mixed $value): string
    {
        $countries = [];
        foreach (explode(',', (string) $value) as $countryCode) {
            $countryCode = strtoupper(trim($countryCode));
            if ($countryCode !== '') {
                $countries[] = preg_replace('/[^A-Z]/', '', $countryCode);
            }
        }

        return implode(',', array_filter($countries));
    }

    public function sanitizeNumber(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return max(0.0, (float) $value);
    }
}
