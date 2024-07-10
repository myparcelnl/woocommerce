<?php

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;

/**
 * @template T of WC_Shipping_Method
 * * @method WC_Shipping_Method make()
 * * @method $this withId(int|string $id)
 * * @method $this withMethodTitle(string $methodTitle)
 * * @method $this withMethodDescription(string $methodDescription)
 * * @method $this withEnabled(string $enabled)
 * * @method $this withTitle(string $title)
 * * @method $this withRates(array $rates)
 * * @method $this withTaxStatus(string $taxStatus)
 * * @method $this withFee(string $fee)
 * * @method $this withMinimumFee(string $minimumFee)
 * * @method $this withInstanceId(string|int $instanceId)
 * * @method $this withInstanceFormFields(array $instanceFormFields)
 * * @method $this withInstanceSettings(array $instanceSettings)
 * * @method $this withAvailability(string $availability)
 * * @method $this withCountries(array $countries)
 * * @method $this withMethodOrder(int $methodOrder)
 * * @method $this withHasSettings(bool $hasSettings)
 * * @method $this withSettingsHtml(string|bool $settingsHtml)
 * * @method $this withSupports(array $supports)
 */
final class WC_Shipping_Method_Factory extends AbstractWcDataFactory
{
    public function getClass(): string
    {
        return WC_Shipping_Method::class;
    }
}
