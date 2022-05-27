<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Settings\Api;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Factory\Account\CarrierConfigurationFactory;
use MyParcelNL\Sdk\src\Model\Account\Account;
use MyParcelNL\Sdk\src\Model\Account\CarrierConfiguration;
use MyParcelNL\Sdk\src\Model\Account\CarrierOptions;
use MyParcelNL\Sdk\src\Model\Account\Shop;
use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Support\Collection;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use MyParcelNL\WooCommerce\includes\Model\Model;
use WCMP_Data;

/**
 * @property null|\MyParcelNL\Sdk\src\Model\Account\Shop                         $shop
 * @property null|\MyParcelNL\Sdk\src\Model\Account\Account                      $account
 * @property Collection|\MyParcelNL\Sdk\src\Model\Account\CarrierOptions[]       $carrier_options
 * @property Collection|\MyParcelNL\Sdk\src\Model\Account\CarrierConfiguration[] $carrier_configurations
 */
class AccountSettings extends Model
{
    use HasApiKey;
    use HasInstance;

    /**
     * @var string Name of the wp_options row the account settings are saved in.
     */
    public const WP_OPTION_KEY = 'woocommerce_myparcel_account_settings';

    /**
     * @var string[]
     */
    protected $attributes = [
        'shop',
        'account',
        'carrier_options',
        'carrier_configurations',
    ];

    /**
     * @var \MyParcelNL\Sdk\src\Support\Collection
     */
    private $settings;

    /**
     * @var bool true when webhooks are disabled
     */
    private $useManualUpdate = false;

    /**
     * @throws \MyParcelNL\Sdk\src\Exception\ApiException
     * @throws \MyParcelNL\Sdk\src\Exception\AccountNotActiveException
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct([]);

        if (! $this->hasApiKey()) {
            return;
        }

        $service  = AccountSettingsService::getInstance();
        $settings = $service->retrieveSettings();

        if (! $settings) {
            return;
        }

        $this->fillProperties($settings);
        $service->createSettingsListeners();
    }

    /**
     * @return \WP_REST_Response
     */
    public static function restRefreshFromApi(): \WP_REST_Response
    {
        return AccountSettingsService::getInstance()->restRefreshSettingsFromApi();
    }

    /**
     * @return null|\MyParcelNL\Sdk\src\Model\Account\Account
     */
    public function getAccount(): ?Account
    {
        return $this->account;
    }

    /**
     * @param  int $carrierId
     *
     * @return null|\MyParcelNL\Sdk\src\Model\Account\CarrierConfiguration
     */
    public function getCarrierConfigurationByCarrierId(int $carrierId): ?CarrierConfiguration
    {
        $carrierConfigurations = $this->getCarrierConfigurations();

        return $carrierConfigurations
            ->filter(
                static function (CarrierConfiguration $carrierConfiguration) use ($carrierId) {
                    return $carrierId === $carrierConfiguration->getCarrier()
                            ->getId();
                }
            )
            ->first();
    }

    /**
     * @return \MyParcelNL\Sdk\src\Model\Account\CarrierConfiguration[]|\MyParcelNL\Sdk\src\Support\Collection
     */
    public function getCarrierConfigurations(): Collection
    {
        return $this->carrier_configurations ?? new Collection();
    }

    /** c
     *
     * @return \MyParcelNL\Sdk\src\Model\Account\CarrierOptions[]|\MyParcelNL\Sdk\src\Support\Collection
     */
    public function getCarrierOptions(): Collection
    {
        return $this->carrier_options ?? new Collection();
    }

    /**
     * @param  int $carrierId
     *
     * @return null|\MyParcelNL\Sdk\src\Model\Account\CarrierOptions
     */
    public function getCarrierOptionsByCarrierId(int $carrierId): ?CarrierOptions
    {
        $carrierOptions = $this->getCarrierOptions();

        return $carrierOptions
            ->filter(
                static function (CarrierOptions $carrierOptions) use ($carrierId) {
                    return $carrierId === $carrierOptions->getCarrier()
                            ->getId();
                }
            )
            ->first();
    }

    /**
     * Returns indexed array with carrier names that are enabled for the current shop.
     *
     * @return \MyParcelNL\Sdk\src\Support\Collection|\MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier[]
     */
    public function getEnabledCarriers(): Collection
    {
        if (! $this->isValid() || ! $this->getCarrierOptions()) {
            return new Collection();
        }

        return $this->getCarrierOptions()
            ->filter(static function (CarrierOptions $carrierOption) {
                return $carrierOption->isEnabled() && WCMP_Data::hasCarrier($carrierOption->getCarrier());
            })
            ->map(static function (CarrierOptions $carrierOptions) {
                return $carrierOptions->getCarrier();
            });
    }

    /**
     * @return null|\MyParcelNL\Sdk\src\Model\Account\Shop
     */
    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    /**
     * @param  string $carrierName
     *
     * @return bool
     */
    public function isEnabledCarrier(string $carrierName): bool
    {
        return (bool) $this->getEnabledCarriers()
            ->filter(static function (AbstractCarrier $carrier) use ($carrierName) {
                return $carrier->getName() === $carrierName;
            })
            ->first();
    }

    /**
     * @return bool whether this is a valid AccountSettings object
     */
    public function isValid(): bool
    {
        return $this->shop instanceof Shop
            && $this->account instanceof Account
            && $this->carrier_options instanceof Collection
            && $this->carrier_configurations instanceof Collection;
    }

    /**
     * @return bool
     */
    public function useManualUpdate(): bool
    {
        return $this->useManualUpdate;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Support\Collection $settings
     *
     * @return void
     */
    private function fillProperties(Collection $settings): void
    {
        $shop                  = $settings->get('shop');
        $account               = $settings->get('account');
        $carrierOptions        = $settings->get('carrier_options');
        $carrierConfigurations = $settings->get('carrier_configurations');

        if (! isset($shop, $account, $carrierOptions, $carrierConfigurations)) {
            Messages::showAdminNotice(
                __('error_settings_account_missing', 'woocommerce-myparcel'),
                Messages::NOTICE_LEVEL_ERROR
            );

            return;
        }

        $this->shop                   = new Shop($shop);
        $account['shops']             = [$shop];
        $this->account                = new Account($account);
        $this->carrier_options        = (new Collection($carrierOptions))->mapInto(CarrierOptions::class);
        $this->carrier_configurations = (new Collection($carrierConfigurations))->map(function (array $data) {
            return CarrierConfigurationFactory::create($data);
        });
    }

    /**
     * @param  string $settingKey
     *
     * @return mixed
     */
    private function get(string $settingKey)
    {
        if (! $this->isValid()) {
            Messages::showAdminNotice(
                __('error_settings_account_missing', 'woocommerce-myparcel'),
                Messages::NOTICE_LEVEL_WARNING
            );
            return null;
        }

        return $this->settings->get($settingKey);
    }
}
