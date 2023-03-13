<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use Exception;
use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Plugin\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class PdkPlaceOrderHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\Pdk\Plugin\Contract\PdkOrderRepositoryInterface
     */
    private $repository;

    public function __construct(PdkOrderRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function apply(): void
    {
        add_action('woocommerce_checkout_order_processed', [$this, 'savePdkOrder'], 10, 3);
    }

    public function savePdkOrder($orderId, $wcPost, $wcOrder): void
    {
        $post = wp_unslash(filter_input_array(INPUT_POST));

        $deliveryOptionsData = $post['myparcelnl_delivery_options'] ?? null;

        if (empty($deliveryOptionsData)) {
            return;
        }

        $deliveryOptions = null;

        try {
            $deliveryOptions = new DeliveryOptions(json_decode(stripslashes($deliveryOptionsData), true));
        } catch (Exception $e) {
            DefaultLogger::error(
                'Error saving pdk order data during checkout.',
                [
                    'exception' => $e,
                    'deliveryOptions' => $deliveryOptions ? $deliveryOptions->toArrayWithoutNull() : null,
                ]
            );
            return;
        }

        $pdkOrder = $this->repository->get($wcOrder);
        $pdkOrder->deliveryOptions = $deliveryOptions;
        $this->repository->update($pdkOrder);
    }
}
