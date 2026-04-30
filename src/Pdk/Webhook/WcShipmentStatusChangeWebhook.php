<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Webhook;

use MyParcelNL\Pdk\App\Webhook\Hook\AbstractHook;
use MyParcelNL\Pdk\Webhook\Model\WebhookSubscription;
use MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcShipmentStatusWebhookService;
use Symfony\Component\HttpFoundation\Request;

final class WcShipmentStatusChangeWebhook extends AbstractHook
{
    /**
     * @var \MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcShipmentStatusWebhookService
     */
    private $shipmentStatusWebhookService;

    /**
     * @param  \MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcShipmentStatusWebhookService $shipmentStatusWebhookService
     */
    public function __construct(WcShipmentStatusWebhookService $shipmentStatusWebhookService)
    {
        $this->shipmentStatusWebhookService = $shipmentStatusWebhookService;
    }

    /**
     * @param  \Symfony\Component\HttpFoundation\Request $request
     *
     * @return void
     */
    public function handle(Request $request): void
    {
        $this->shipmentStatusWebhookService->handle($this->getHookBody($request));
    }

    /**
     * @return string
     */
    protected function getHookEvent(): string
    {
        return WebhookSubscription::SHIPMENT_STATUS_CHANGE;
    }
}
