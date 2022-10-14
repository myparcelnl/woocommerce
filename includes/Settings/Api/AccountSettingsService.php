<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Settings\Api;

defined('ABSPATH') or die();

use Exception;
use MyParcelNL\Sdk\src\Model\Account\CarrierConfiguration;
use MyParcelNL\Sdk\src\Model\Account\CarrierOptions;
use MyParcelNL\Sdk\src\Model\Account\Shop;
use MyParcelNL\Sdk\src\Services\Web\AccountWebService;
use MyParcelNL\Sdk\src\Services\Web\CarrierConfigurationWebService;
use MyParcelNL\Sdk\src\Services\Web\CarrierOptionsWebService;
use MyParcelNL\Sdk\src\Support\Collection;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use MyParcelNL\WooCommerce\includes\Settings\Listener\ApiKeySettingsListener;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;
use WCMP_Log;
use WP_REST_Response;

class AccountSettingsService
{
    use HasApiKey;
    use HasInstance;

    /**
     * Load the account settings from the API, and save them to wp options.
     *
     * @param  null|string $apiKey
     *
     * @return bool
     */
    public function refreshSettingsFromApi(string $apiKey): bool
    {
        try {
            $settings = $this->fetchFromApi($apiKey);
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
     * @throws \Exception
     */
    public function removeSettings(): void
    {
        $this->deleteWebhooks();
        $this->deleteSettingsFromDatabase();
    }

    /**
     * @return \WP_REST_Response
     * @throws \Exception
     */
    public function restRefreshSettingsFromApi(): WP_REST_Response
    {
        $response = new WP_REST_Response();
        $response->set_status(200);

        $this->ensureHasApiKey();

        if (! $this->refreshSettingsFromApi($this->getApiKey())) {
            $response->set_status(400);
        }

        return $response;
    }

    /**
     * @return null|\MyParcelNL\Sdk\src\Support\Collection
     */
    public function retrieveSettings(): ?Collection
    {
        $options = get_option(AccountSettings::WP_OPTION_KEY);

        if (! $options) {
            return null;
        }

        return new Collection($options);
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
    private function fetchFromApi(string $apiKey): Collection
    {
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
}
