<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\LanguageService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Model\PdkCartFee;
use MyParcelNL\Pdk\Plugin\Service\DeliveryOptionsFeesService;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use WC_Cart;

final class CartFeesHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        add_action('woocommerce_cart_calculate_fees', [$this, 'calculateDeliveryOptionsFees'], 20);
    }

    /**
     * Get delivery fee in your order overview, at the front of the website
     *
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function calculateDeliveryOptionsFees(WC_Cart $cart): void
    {
        if (! defined('DOING_AJAX') && is_admin()) {
            return;
        }

        $post = wp_unslash(filter_input_array(INPUT_POST));

        if (isset($post['post_data'])) {
            // non-default post data for AJAX calls
            parse_str($post['post_data'], $postData);
        } else {
            // checkout finalization
            $postData = $post;
        }

        $deliveryOptionsData = $postData['myparcelnl_delivery_options'] ?? null;

        if (empty($deliveryOptionsData)) {
            return;
        }

        $deliveryOptions = null;

        try {
            $deliveryOptions = new DeliveryOptions(json_decode(stripslashes($deliveryOptionsData), true));

            /** @var DeliveryOptionsFeesService $feesService */
            $feesService = Pdk::get(DeliveryOptionsFeesService::class);
            $fees        = $feesService->getFees($deliveryOptions);
        } catch (Exception $e) {
            DefaultLogger::error(
                'Error calculating delivery options fees.',
                [
                    'exception' => $e,
                    'deliveryOptions' => $deliveryOptions ? $deliveryOptions->toArrayWithoutNull() : null,
                ]
            );
            return;
        }

        // TODO: add tax support
        $tax = 0;

        $fees->each(function (PdkCartFee $fee) use ($tax, $cart) {
            $cart->add_fee(LanguageService::translate($fee->translation), $fee->amount, (bool) $tax, $tax ?: '');
        });
    }
}
