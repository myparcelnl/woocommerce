<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Settings\Api;

defined('ABSPATH') or die();

use Exception;
use MyParcelNL\Sdk\src\Exception\AccountNotActiveException;
use MyParcelNL\Sdk\src\Exception\ApiException;
use MyParcelNL\Sdk\src\Exception\MissingFieldException;
use MyParcelNL\Sdk\src\Model\Account\CarrierConfiguration;
use MyParcelNL\Sdk\src\Model\Account\CarrierOptions;
use MyParcelNL\Sdk\src\Model\Account\Shop;
use MyParcelNL\Sdk\src\Services\Web\AccountWebService;
use MyParcelNL\Sdk\src\Services\Web\CarrierConfigurationWebService;
use MyParcelNL\Sdk\src\Services\Web\CarrierOptionsWebService;
use MyParcelNL\Sdk\src\Services\Web\OrderWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\OrderStatusChangeWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopCarrierAccessibilityUpdatedWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopCarrierConfigurationUpdatedWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopUpdatedWebhookWebService;
use MyParcelNL\Sdk\src\Support\Collection;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use MyParcelNL\WooCommerce\includes\Settings\Listener\ApiKeySettingsListener;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;
use WCMP_API;
use WCMP_Log;
use WCMYPA_Settings;
use WP_REST_Request;
use WP_REST_Response;
use WPO\WC\MyParcel\Compatibility\WC_Core;

class AccountSettingsService
{
    use HasApiKey;
    use HasInstance;

    /**
     * Webhooks that should refresh the account settings when triggered.
     *
     * @var class-string[]
     */
    public const RELATED_WEBHOOKS = [
        ShopCarrierAccessibilityUpdatedWebhookWebService::class,
        ShopCarrierConfigurationUpdatedWebhookWebService::class,
        ShopUpdatedWebhookWebService::class,
    ];
    /**
     * 2 : Package shipment barcode printed
     * 12: Letter shipment barcode printed
     * 14: Digital stamp barcode printed
     */
    public const COMPLETED_ORDER_STATUSES = [
        2,
        12,
        14,
    ];

    /**
     * When a setting is updated, the old value is still in the settings collection, so you cannot use
     * refreshSettingsFromApi.
     */
    public function createSettingsListeners(): void
    {
        (new ApiKeySettingsListener([$this, 'removeSettings']))->listen();
    }

    /**
     * @throws \Exception
     */
    public function removeSettings(): void
    {
        $this->deleteWebhooks();
        $this->deleteSettingsFromDatabase();
    }

    /**
     * Load the account settings from the API, and save them to wp options.
     *
     * @return bool
     */
    public function refreshSettingsFromApi(): bool
    {
        try {
            $settings = $this->fetchFromApi();
            $this->saveSettingsToDatabase($settings);
            Messages::showAdminNotice(
                __('notice_settings_fetched_from_api', 'woocommerce-myparcel'),
                Messages::NOTICE_LEVEL_SUCCESS
            );

            return true;
        } catch (Exception $e) {
            WCMP_Log::add('Could not load account settings');
            WCMP_Log::add($e->getMessage());
        }

        return false;
    }

    /**
     * @return \WP_REST_Response
     */
    public function restRefreshSettingsFromApi(): WP_REST_Response
    {
        $response = new WP_REST_Response();
        $response->set_status(200);

        if (! $this->refreshSettingsFromApi()) {
            $response->set_status(400);
        }

        return $response;
    }

    /**
     * @param  bool $triedFetching
     *
     * @return null|\MyParcelNL\Sdk\src\Support\Collection
     * @throws \MyParcelNL\Sdk\src\Exception\AccountNotActiveException
     * @throws \MyParcelNL\Sdk\src\Exception\ApiException
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     */
    public function retrieveSettings(bool $triedFetching = false): ?Collection
    {
        $options = get_option(AccountSettings::WP_OPTION_KEY);

        if (! $options && ! $triedFetching) {
            $this->refreshSettingsFromApi();
            return $this->retrieveSettings(true) ?? new Collection();
        }

        return new Collection($options);
    }

    /**
     * @throws \Exception
     */
    public function setUpOrderStatusWebhook(): void
    {
        $subscriptionService = new WebhookSubscriptionService();
        $orderStatusChangeWebhook = OrderStatusChangeWebhookWebService::class;
        $webhookWebService = (new $orderStatusChangeWebhook())->setApiKey($this->ensureHasApiKey());

        $subscriptionService->create($webhookWebService, [$this, 'updateOrderStatus']);
    }

    /**
     * Install the webhooks relating to account settings.
     *
     * @return void
     * @throws \Exception
     */
    public function setUpWebhooks(): void
    {
        $apiKey              = $this->ensureHasApiKey();
        $subscriptionService = new WebhookSubscriptionService();

        foreach (self::RELATED_WEBHOOKS as $webhookWebServiceClass) {
            $webhookWebService = (new $webhookWebServiceClass())->setApiKey($apiKey);

            $subscriptionService->create($webhookWebService, [$this, 'restRefreshSettingsFromApi']);
        }
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Support\Collection $settings
     *
     * @return array
     * @TODO sdk#326 remove this entire function and replace with toArray
     */
    private function createArray(Collection $settings): array
    {
        /** @var \MyParcelNL\Sdk\src\Model\Account\Shop $shop */
        $shop = $settings->get('shop');
        /** @var \MyParcelNL\Sdk\src\Model\Account\Account $account */
        $account = $settings->get('account');
        /** @var \MyParcelNL\Sdk\src\Model\Account\CarrierOptions[]|Collection $carrierOptions */
        $carrierOptions = $settings->get('carrier_options');
        /** @var \MyParcelNL\Sdk\src\Model\Account\CarrierConfiguration[]|Collection $carrierConfigurations */
        $carrierConfigurations = $settings->get('carrier_configurations');

        return [
            'shop'                   => [
                'id'   => $shop->getId(),
                'name' => $shop->getName(),
            ],
            'account'                => $account->toArray(),
            'carrier_options'        => array_map(static function (CarrierOptions $carrierOptions) {
                $carrier = $carrierOptions->getCarrier();
                return [
                    'carrier'  => [
                        'human' => $carrier->getHuman(),
                        'id'    => $carrier->getId(),
                        'name'  => $carrier->getName(),
                    ],
                    'enabled'  => $carrierOptions->isEnabled(),
                    'label'    => $carrierOptions->getLabel(),
                    'optional' => $carrierOptions->isOptional(),
                ];
            }, $carrierOptions->all()),
            'carrier_configurations' => array_map(static function (CarrierConfiguration $carrierConfiguration) {
                $defaultDropOffPoint = $carrierConfiguration->getDefaultDropOffPoint();
                $carrier             = $carrierConfiguration->getCarrier();
                return [
                    'carrier_id'                        => $carrier->getId(),
                    'default_drop_off_point'            => $defaultDropOffPoint ? [
                        'box_number'        => $defaultDropOffPoint->getBoxNumber(),
                        'cc'                => $defaultDropOffPoint->getCc(),
                        'city'              => $defaultDropOffPoint->getCity(),
                        'location_code'     => $defaultDropOffPoint->getLocationCode(),
                        'location_name'     => $defaultDropOffPoint->getLocationName(),
                        'number'            => $defaultDropOffPoint->getNumber(),
                        'number_suffix'     => $defaultDropOffPoint->getNumberSuffix(),
                        'postal_code'       => $defaultDropOffPoint->getPostalCode(),
                        'region'            => $defaultDropOffPoint->getRegion(),
                        'retail_network_id' => $defaultDropOffPoint->getRetailNetworkId(),
                        'state'             => $defaultDropOffPoint->getState(),
                        'street'            => $defaultDropOffPoint->getStreet(),
                    ] : null,
                    'default_drop_off_point_identifier' => $carrierConfiguration->getDefaultDropOffPointIdentifier(),
                ];
            }, $carrierConfigurations->all()),
        ];
    }

    /**
     * @return bool
     */
    private function deleteSettingsFromDatabase(): bool
    {
        return update_option(AccountSettings::WP_OPTION_KEY, null);
    }

    private function deleteWebhooks(): void
    {
        (new WebhookSubscriptionService())->deleteAll();
    }

    /**
     * @return \MyParcelNL\Sdk\src\Support\Collection
     * @throws \MyParcelNL\Sdk\src\Exception\AccountNotActiveException
     * @throws \MyParcelNL\Sdk\src\Exception\ApiException
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     * @throws \Exception
     */
    private function fetchFromApi(): Collection
    {
        $apiKey = $this->ensureHasApiKey();

        $accountService = (new AccountWebService())->setApiKey($apiKey);

        $account = $accountService->getAccount();
        $shop    = $account->getShops()
            ->first();

        $carrierOptionsService = (new CarrierOptionsWebService())->setApiKey($apiKey);
        $carrierOptions        = $carrierOptionsService->getCarrierOptions($shop->getId());

        $carrierConfigurationService = (new CarrierConfigurationWebService())->setApiKey($apiKey);
        $carrierConfigurations       = $this->loadCarrierConfigurations(
            $carrierConfigurationService,
            $shop
        );

        return new Collection([
            'shop'                   => $shop,
            'account'                => $account,
            'carrier_options'        => $carrierOptions,
            'carrier_configurations' => $carrierConfigurations,
        ]);
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Services\Web\CarrierConfigurationWebService $service
     * @param  \MyParcelNL\Sdk\src\Model\Account\Shop                          $shop
     *
     * @return \MyParcelNL\Sdk\src\Support\Collection
     * @throws \MyParcelNL\Sdk\src\Exception\AccountNotActiveException
     * @throws \MyParcelNL\Sdk\src\Exception\ApiException
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     */
    private function loadCarrierConfigurations(
        CarrierConfigurationWebService $service,
        Shop                           $shop
    ):
    Collection {
        return $service->getCarrierConfigurations($shop->getId(), true);
    }

    /**
     * Save this object to wp options and return success.
     *
     * @param  \MyParcelNL\Sdk\src\Support\Collection $settings
     *
     * @return bool
     */
    private function saveSettingsToDatabase(Collection $settings): bool
    {
        $array = $this->createArray($settings);

        return update_option(AccountSettings::WP_OPTION_KEY, $array);
    }

    /**
     * @param  \WP_REST_Request $response
     *
     * @return void
     * @throws \Exception
     */
    public function updateOrderStatus(WP_REST_Request $request): void
    {
        $requestBody = $request->get_body();
        $jsonBody    = json_decode($requestBody, true);
        $orderId     = $jsonBody['data']['hooks'][0]['order'];
        $order       = $this->getOrderAsArray($orderId);

        if (! $order || ! $order['order_shipments']) {
            return;
        }

        $shipment = $order['order_shipments'][0];

        if (! ($shipment['external_shipment_identifier']
            && in_array($shipment['shipment']['status'], self::COMPLETED_ORDER_STATUSES, true))) {
            return;
        }

        $wcOrder = WC_Core::get_order($order['external_identifier']);
        $wcOrder->update_status(
            WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_AUTOMATIC_ORDER_STATUS),
            '',
            true
        );
    }

    /**
     * @param $orderId
     *
     * @return null|array
     * @throws \Exception
     */
    private function getOrderAsArray($orderId): ?array
    {
        $orderWebService = new OrderWebService();
        $orderWebService->setApiKey($this->ensureHasApiKey());

        try {
            $order = $orderWebService->getOrder($orderId);
        } catch (Exception $e) {
            WCMP_Log::add($e->getMessage());
            return null;
        }

        return $order;
    }
}
