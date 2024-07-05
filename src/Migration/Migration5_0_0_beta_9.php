<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use Generator;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Migration\Pdk\SettingsMigration;

/**
 * TODO: Delete this migration before 5.0.0 release
 * Migrates beta.0 to beta.8 settings to the new format. Pre-beta versions are migrated in the 5.0.0 migration.
 */
final class Migration5_0_0_beta_9 extends SettingsMigration
{
    /**
     * @return void
     */
    public function down(): void
    {
        // Nothing to do here
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return '5.0.0-beta.9';
    }

    /**
     * @return void
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function up(): void
    {
        /** @var \MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface $settingsRepository */
        $settingsRepository = Pdk::get(PdkSettingsRepositoryInterface::class);

        $settings = $settingsRepository->all();

        $newSettings = $this->transformSettings(
            $settings->checkout->toArray(),
            $this->getCheckoutTransformationMap()
        );

        $checkoutSettings = new CheckoutSettings($newSettings);

        $settingsRepository->storeSettings($checkoutSettings);
    }

    /**
     * @return \Generator
     */
    private function getCheckoutTransformationMap(): Generator
    {
        yield [
            self::TRANSFORM_KEY_SOURCE    => CheckoutSettings::ALLOWED_SHIPPING_METHODS,
            self::TRANSFORM_KEY_TARGET    => CheckoutSettings::ALLOWED_SHIPPING_METHODS,
            self::TRANSFORM_KEY_TRANSFORM => function ($value): array {
                if (Arr::isAssoc((array) $value)) {
                    return $value;
                }

                /** @var string[] $keys */
                $keys = Pdk::get('allowedShippingMethodsKeys');
                // Create a new empty shipping methods map
                $newValue = array_combine($keys, array_fill(0, count($keys), []));

                $newValue[DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME] = (array) $value;

                return $newValue;
            },
        ];
    }
}
