<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhooks\Hooks;

defined('ABSPATH') or die();

use Exception;
use MyParcelNL\Sdk\src\Collection\Fulfilment\OrderCollection;
use MyParcelNL\Sdk\src\Model\Fulfilment\Order;
use MyParcelNL\Sdk\src\Services\Web\Webhook\OrderStatusChangeWebhookWebService;
use MyParcelNL\WooCommerce\includes\Webhook\Hooks\AbstractWebhook;
use WCMP_Log;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WP_REST_Request;
use WP_REST_Response;
use WPO\WC\MyParcel\Compatibility\WC_Core;

class OrderStatusWebhook extends AbstractWebhook
{
    /**
     * 2 : Package shipment barcode printed
     * 12: Letter shipment barcode printed
     * 14: Digital stamp barcode printed
     */
    private const COMPLETED_SHIPMENT_STATUSES = [
        2,
        12,
        14,
    ];

    /**
     * @param  \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function getCallback(WP_REST_Request $request): WP_REST_Response
    {
        $requestBody = $request->get_body();
        $jsonBody    = json_decode($requestBody, true);
        $orderUuid   = $jsonBody['data']['hooks'][0]['order'] ?? null;

        try {
            /** @type \MyParcelNL\Sdk\src\Model\Fulfilment\Order $order */
            $order = OrderCollection::query($this->ensureHasApiKey(), ['uuid' => $orderUuid])
                ->first();
        } catch (Exception $e) {
            WCMP_Log::add($e->getMessage());
            return $this->getUnprocessableEntityResponse();
        }

        if (! $order || ! $order->getOrderShipments() || ! $this->isPrinted($order)) {
            return $this->getSkippedResponse();
        }

        $this->updateWooCommerceOrderStatus($order);
        $this->updateOrderBarcode($order);

        return $this->getNoContentResponse();
    }

    /**
     * @return class-string<\MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService>[]
     */
    protected function getHooks(): array
    {
        return [
            OrderStatusChangeWebhookWebService::class,
        ];
    }

    /**
     * @param $order
     *
     * @return bool
     */
    private function isPrinted($order): bool
    {
        $shipment                   = $order->getOrderShipments()[0];
        return in_array(
            $shipment['shipment']['status'],
            self::COMPLETED_SHIPMENT_STATUSES,
            true
        );
    }

    private function updateOrderBarcode(Order $order): void
    {
        $orderData      = get_post_meta($order->getExternalIdentifier(), WCMYPA_Admin::META_PPS, true);
        $orderShipments = $order->getOrderShipments();
        $barcode        = end($orderShipments)['shipment']['barcode'];

        if (! $barcode) {
            return;
        }

        $orderData[WCMYPA_Admin::META_TRACK_TRACE] = $barcode;

        update_post_meta($order->getExternalIdentifier(), WCMYPA_Admin::META_PPS, $orderData);
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Fulfilment\Order $order
     *
     * @return void
     */
    private function updateWooCommerceOrderStatus(Order $order): void
    {
        $wcOrder = WC_Core::get_order($order->getExternalIdentifier());
        $wcOrder->update_status(
            WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_AUTOMATIC_ORDER_STATUS),
            '',
            true
        );
    }
}
