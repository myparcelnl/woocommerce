<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\App\Cart\Model\PdkCartFee;
use MyParcelNL\Pdk\App\DeliveryOptions\Contract\DeliveryOptionsFeesServiceInterface;
use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Pdk\Service\WcTaxService;
use WC_Cart;

/**
 * Adds the delivery-options fees (signature, same-day, pickup discount, ...) to the cart total
 * during checkout.
 *
 * Classic checkout re-posts the selection in $_POST on every recalculation; blocks checkout
 * recalculates via a stateless Store API request that carries no post data. The blocks frontend
 * therefore pushes the selection through the cart-extensions endpoint, the update callback stashes
 * it in the WC session, and resolveDeliveryOptionsData() reads from whichever source applies so the
 * fee logic stays identical for both checkouts.
 */
final class CartFeesHooks implements WordPressHooksInterface
{
    private const DELIVERY_OPTIONS_SESSION_KEY = '_myparcelcom_delivery_options';

    /** @var WcTaxService */
    private $taxService;

    public function __construct(WcTaxService $taxService) { $this->taxService = $taxService; }

    public function apply(): void
    {
        add_action('woocommerce_cart_calculate_fees', [$this, 'calculateDeliveryOptionsFees'], 20);

        // apply() runs on init priority 9999, after woocommerce_init has already fired, so register
        // the Store API callback directly instead of deferring it — still before any REST request.
        $this->registerStoreApiUpdateCallback();

        // Drop the stashed selection once it's no longer relevant, so it can't apply to a later cart.
        add_action('woocommerce_checkout_order_processed', [$this, 'clearDeliveryOptionsSession']);
        add_action('woocommerce_blocks_checkout_order_processed', [$this, 'clearDeliveryOptionsSession']);
        add_action('woocommerce_cart_emptied', [$this, 'clearDeliveryOptionsSession']);
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

        // Local pickup ("Afhalen in de winkel") ships no parcel, so MyParcel delivery-options
        // surcharges must not be charged even if a selection lingers from before the customer
        // switched away from "Verzenden".
        if ($this->isLocalPickupChosen()) {
            return;
        }

        $deliveryOptionsData = $this->resolveDeliveryOptionsData();

        if (empty($deliveryOptionsData)) {
            return;
        }

        $deliveryOptions = null;

        try {
            $deliveryOptions = new DeliveryOptions($deliveryOptionsData);

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
            $amount = $fee->amount;

            // For pickup fee, ensure total shipping costs don't go below 0
            if ($fee->id === 'delivery_type_pickup') {
                $shippingTotal = $cart->get_shipping_total();
                // Limit the pickup discount so shipping + pickup >= 0
                $amount = max(-$shippingTotal, $fee->amount);
            }

            $cart->add_fee(Language::translate($fee->translation), $amount, (bool) $tax, $tax);
        });
    }

    /**
     * Whether the customer chose local pickup for the whole cart. Matches both the classic
     * shipping-zone method (`local_pickup:N`) and the blocks "Afhalen in de winkel" method
     * (`pickup_location:N`). Returns false on mixed carts that still ship a parcel.
     */
    private function isLocalPickupChosen(): bool
    {
        if (! WC()->session) {
            return false;
        }

        $chosenMethods = (array) WC()->session->get('chosen_shipping_methods', []);

        if (empty($chosenMethods)) {
            return false;
        }

        foreach ($chosenMethods as $method) {
            $method = (string) $method;

            if (strpos($method, 'local_pickup') !== 0 && strpos($method, 'pickup_location') !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Stashes the blocks-checkout selection in the session when extensionCartUpdate fires, for
     * calculateDeliveryOptionsFees() to read on the recalculation that follows.
     */
    public function registerStoreApiUpdateCallback(): void
    {
        if (! function_exists('woocommerce_store_api_register_update_callback')) {
            return;
        }

        woocommerce_store_api_register_update_callback([
            'namespace' => PdkBootstrapper::PLUGIN_NAMESPACE . '-delivery-options',
            'callback'  => static function (array $data): void {
                if (WC()->session) {
                    WC()->session->set(self::DELIVERY_OPTIONS_SESSION_KEY, $data);
                }
            },
        ]);
    }

    public function clearDeliveryOptionsSession(): void
    {
        if (WC()->session) {
            WC()->session->set(self::DELIVERY_OPTIONS_SESSION_KEY, null);
        }
    }

    /**
     * Returns the selection from the posted checkout input (classic) or, failing that, the WC
     * session written by the Store API callback (blocks).
     */
    private function resolveDeliveryOptionsData(): ?array
    {
        $post = wp_unslash(filter_input_array(INPUT_POST));

        if (isset($post['post_data'])) {
            // non-default post data for AJAX calls
            parse_str($post['post_data'], $postData);
        } else {
            // checkout finalization
            $postData = $post ?? [];
        }

        $posted = $postData[Pdk::get('checkoutHiddenInputName')] ?? null;

        if (! empty($posted)) {
            return json_decode(stripslashes($posted), true);
        }

        // Blocks checkout: the Store API update callback stashes the selection in the session.
        if (WC()->session) {
            $sessionData = WC()->session->get(self::DELIVERY_OPTIONS_SESSION_KEY);

            if (! empty($sessionData)) {
                return $sessionData;
            }
        }

        return null;
    }
}
