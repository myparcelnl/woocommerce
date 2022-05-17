<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhooks\Hooks;

defined('ABSPATH') or die();

use Exception;
use MyParcelNL\Sdk\src\Collection\Fulfilment\OrderCollection;
use MyParcelNL\Sdk\src\Services\Web\Webhook\OrderStatusChangeWebhookWebService;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;
use WCMP_Export_Consignments;
use WCMP_Log;
use WCMP_Settings_Data;
use WCMYPA_Settings;
use WP_REST_Request;
use WPO\WC\MyParcel\Compatibility\WC_Core;

class OrderStatusWebhook
{
    use HasApiKey;
    use HasInstance;

    /**
     * 2 : Package shipment barcode printed
     * 12: Letter shipment barcode printed
     * 14: Digital stamp barcode printed
     */
    public const COMPLETED_SHIPMENT_STATUSES = [
        2,
        12,
        14,
    ];

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $changeOrderStatusAfter = WCMP_Export_Consignments::getSetting(WCMYPA_Settings::SETTING_CHANGE_ORDER_STATUS_AFTER);
        $exportMode             = WCMP_Export_Consignments::getSetting(WCMYPA_Settings::SETTING_EXPORT_MODE);

        if (WCMP_Settings_Data::CHANGE_STATUS_AFTER_PRINTING === $changeOrderStatusAfter && WCMP_Settings_Data::EXPORT_MODE_PPS === $exportMode) {
            $service = (new OrderStatusChangeWebhookWebService())->setApiKey($this->ensureHasApiKey());
            (new WebhookSubscriptionService())->create($service, [$this, 'updateOrderStatus']);
        }
    }

    /**
     * @param  \WP_REST_Request $request
     *
     * @return void
     * @throws \Exception
     */
    public function updateOrderStatus(WP_REST_Request $request): void
    {
        $requestBody = $request->get_body();
        $jsonBody    = json_decode($requestBody, true);
        $orderUuid   = $jsonBody['data']['hooks'][0]['order'] ?? null;

        try {
            $order = OrderCollection::query($this->ensureHasApiKey(), ['uuid' => $orderUuid])->first();
        } catch (Exception $e) {
            WCMP_Log::add($e->getMessage());
            return;
        }

        if (! $order->getOrderShipments()) {
            return;
        }

        $shipment                   = $order->getOrderShipments()[0];
        $shipmentHasCompletedStatus = in_array(
            $shipment['shipment']['status'],
            self::COMPLETED_SHIPMENT_STATUSES,
            true
        );

        if (! ($shipment['external_shipment_identifier'] && $shipmentHasCompletedStatus)) {
            return;
        }

        $wcOrder = WC_Core::get_order($order->getExternalIdentifier());
        $wcOrder->update_status(
            WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_AUTOMATIC_ORDER_STATUS),
            '',
            true
        );
    }
}
