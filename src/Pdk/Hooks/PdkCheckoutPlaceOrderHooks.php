<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use Exception;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use WC_Order;

/**
 * Hooks into the checkout process when an order is placed.
 */
final class PdkCheckoutPlaceOrderHooks implements WordPressHooksInterface
{
    /**
     * @var PdkOrderRepositoryInterface
     */
    private $repository;

    /**
     * @param  PdkOrderRepositoryInterface $repository
     */
    public function __construct(PdkOrderRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function apply(): void
    {
        add_action('woocommerce_checkout_order_processed', [$this, 'saveDeliveryOptions'], 10, 3);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'saveBlocksDeliveryOptions'], 10, 2);
    }

    /**
     * Saves the delivery options to the new PDK order.
     *
     * @param  WC_Order         $wcOrder
     * @param  \WP_REST_Request $request Carries the raw checkout body with the selection.
     *
     * @return void
     */
    public function saveBlocksDeliveryOptions(WC_Order $wcOrder, $request): void
    {
        $namespace = PdkBootstrapper::PLUGIN_NAMESPACE;

        try {
            $body = $request->get_body();

            if (! is_string($body) || '' === $body) {
                return;
            }

            $postData            = json_decode(wp_unslash($body), true);
            $deliveryOptionsData = is_array($postData)
                ? ($postData['extensions']["$namespace-delivery-options"] ?? null)
                : null;

            if (empty($deliveryOptionsData)) {
                return;
            }

            $deliveryOptions = new DeliveryOptions($deliveryOptionsData);

            $pdkOrder = $this->repository->get($wcOrder);

            $pdkOrder->deliveryOptions = $deliveryOptions;

            $this->repository->update($pdkOrder);
        } catch (Exception $e) {
            Logger::error(
                'Error saving pdk order data during checkout.',
                [
                    'exception'       => $e,
                    'deliveryOptions' => $deliveryOptionsData ?? null,
                ]
            );
        }
    }

    /**
     * Saves the delivery options to the new PDK order.
     *
     * @param  mixed    $orderId
     * @param  mixed    $wcPost
     * @param  WC_Order $wcOrder
     *
     * @return void
     */
    public function saveDeliveryOptions($orderId, $wcPost, WC_Order $wcOrder): void
    {
        try {
            $post                = wp_unslash(filter_input_array(INPUT_POST));
            $deliveryOptionsData = $post[Pdk::get('checkoutHiddenInputName')] ?? null;

            if (empty($deliveryOptionsData)) {
                return;
            }

            $deliveryOptions = new DeliveryOptions(json_decode(stripslashes($deliveryOptionsData), true));

            $pdkOrder = $this->repository->get($wcOrder);

            $pdkOrder->deliveryOptions = $deliveryOptions;

            $this->repository->update($pdkOrder);
        } catch (Exception $e) {
            Logger::error(
                'Error saving pdk order data during checkout.',
                [
                    'exception'       => $e,
                    'deliveryOptions' => $deliveryOptionsData ?? null,
                ]
            );
        }
    }
}
