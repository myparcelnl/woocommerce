<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Base\Service\CountryCodes;
use MyParcelNL\Pdk\Facade\AccountSettings;
use MyParcelNL\WooCommerce\WooCommerce\Address\EoriNumberField;
use MyParcelNL\WooCommerce\WooCommerce\Address\VatNumberField;

class TaxFieldsHooks extends AbstractFieldsHooks
{
    /**
     * @return bool
     */
    protected function addToBilling(): bool
    {
        return false;
    }

    /**
     * @return string[]
     */
    protected function getApplicableCountries(): array
    {
        // TODO: Move to pdk as 'countriesWithTaxFields'
        return CountryCodes::EU_COUNTRIES;
    }

    /**
     * @return \MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface[]
     */
    protected function getCustomFields(): array
    {
        return [
            new VatNumberField(),
            new EoriNumberField(),
        ];
    }

    /**
     * @return string
     */
    protected function getName(): string
    {
        return 'taxFields';
    }

    /**
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return AccountSettings::hasTaxFields();
    }
}
