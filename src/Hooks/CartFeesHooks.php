<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\App\Cart\Model\PdkCartFee;
use MyParcelNL\Pdk\App\DeliveryOptions\Contract\DeliveryOptionsFeesServiceInterface;
use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Pdk\Service\WcTaxService;
use WC_Cart;

final class CartFeesHooks implements WordPressHooksInterface
{
    /** @var WcTaxService */
    private $taxService;

    public function __construct(WcTaxService $taxService)
    {
        $this->taxService = $taxService;
    }

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

        $deliveryOptionsData = $postData[Pdk::get('checkoutHiddenInputName')] ?? null;

        if (empty($deliveryOptionsData)) {
            return;
        }

        $deliveryOptions = null;

        try {
            $deliveryOptions = new DeliveryOptions(json_decode(stripslashes($deliveryOptionsData), true));

            /** @var DeliveryOptionsFeesServiceInterface $feesService */
            $feesService = Pdk::get(DeliveryOptionsFeesServiceInterface::class);
            $fees        = $feesService->getFees($deliveryOptions);
        } catch (Exception $e) {
            Logger::error(
                'Error calculating delivery options fees.',
                [
                    'exception' => $e->getMessage(),
                    'deliveryOptions' => $deliveryOptions ? $deliveryOptions->toArrayWithoutNull() : null,
                ]
            );
            return;
        }

        $tax = $this->taxService->getShippingTaxClass();

        $fees->each(function (PdkCartFee $fee) use ($tax, $cart) {
            $cart->add_fee(Language::translate($fee->translation), $fee->amount, (bool) $tax, $tax);
        });
    }
}
