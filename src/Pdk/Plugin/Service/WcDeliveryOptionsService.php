<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Service;

use MyParcelNL\Pdk\Plugin\Service\DeliveryOptionsServiceInterface;

final class WcDeliveryOptionsService implements DeliveryOptionsServiceInterface
{
    private const HOOKS = [
        'woocommerce_after_cart',
        'woocommerce_after_cart_contents',
        'woocommerce_after_cart_table',
        'woocommerce_after_checkout_billing',
        'woocommerce_after_checkout_billing_form',
        'woocommerce_after_checkout_form',
        'woocommerce_after_checkout_shipping',
        'woocommerce_after_checkout_shipping_form',
        'woocommerce_after_order_notes',
        'woocommerce_before_cart',
        'woocommerce_before_cart_contents',
        'woocommerce_before_cart_table',
        'woocommerce_before_checkout_billing',
        'woocommerce_before_checkout_billing_form',
        'woocommerce_before_checkout_form',
        'woocommerce_before_checkout_shipping',
        'woocommerce_before_checkout_shipping_form',
        'woocommerce_before_order_notes',
    ];

    public function getPositions(): array
    {
        return array_combine(
            self::HOOKS,
            array_map(static function (string $hook) {
                return 'wc_hook_' . $hook;
            }, self::HOOKS)
        );
    }
}
