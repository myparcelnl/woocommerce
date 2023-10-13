<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Base\Contract\Arrayable;
use MyParcelNL\Pdk\Base\Service\CountryCodes;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Tests\Factory\Contract\FactoryInterface;
use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

/**
 * @template T of WC_Order
 * @method WC_Order make()
 * @method $this withBillingCity(string $billingCity)
 * @method $this withBillingCompany(string $billingCompany)
 * @method $this withBillingCountry(string $billingCountry)
 * @method $this withBillingEmail(string $billingEmail)
 * @method $this withBillingFirstName(string $billingFirstName)
 * @method $this withBillingLastName(string $billingLastName)
 * @method $this withBillingPhone(string $billingPhone)
 * @method $this withBillingPostcode(string $billingPostcode)
 * @method $this withBillingState(string $billingState)
 * @method $this withCustomerNote(string $customerNote)
 * @method $this withDateCreated(WC_DateTime $dateCreated)
 * @method $this withId(int $id)
 * @method $this withShippingCity(string $shippingCity)
 * @method $this withShippingCompany(string $shippingCompany)
 * @method $this withShippingCountry(string $shippingCountry)
 * @method $this withShippingEmail(string $shippingEmail)
 * @method $this withShippingFirstName(string $shippingFirstName)
 * @method $this withShippingLastName(string $shippingLastName)
 * @method $this withShippingPhone(string $shippingPhone)
 * @method $this withShippingPostcode(string $shippingPostcode)
 * @method $this withShippingState(string $shippingState)
 * @method $this withStatus(string $value)
 */
final class WC_Order_Factory extends AbstractWcDataFactory
{
    public function getClass(): string
    {
        return WC_Order::class;
    }

    public function withBillingAddress1(string $billingAddress1): self
    {
        return $this->with(['billing_address_1' => $billingAddress1]);
    }

    public function withBillingAddress2(string $billingAddress2): self
    {
        return $this->with(['billing_address_2' => $billingAddress2]);
    }

    /**
     * @param  array|Collection $items
     *
     * @return $this
     */
    public function withItems($items): self
    {
        if ($items instanceof Arrayable) {
            return $this->withItems($items->toArrayWithoutNull());
        }

        if ($items instanceof FactoryInterface) {
            return $this->withItems($items->make());
        }

        return $this->with([
            'items' => array_map(static function ($item) {
                return $item instanceof FactoryInterface ? $item->make() : $item;
            }, $items),
        ]);
    }

    public function withShippingAddress1(string $shippingAddress1): self
    {
        return $this->with(['shipping_address_1' => $shippingAddress1]);
    }

    public function withShippingAddress2(string $shippingAddress2): self
    {
        return $this->with(['shipping_address_2' => $shippingAddress2]);
    }

    public function withShippingAddressInBelgium(): self
    {
        return $this
            ->withShippingAddress1('Adriaan Brouwerstraat 16')
            ->withShippingCity('Antwerpen')
            ->withShippingCompany('MyParcel BE')
            ->withShippingCountry('BE')
            ->withShippingFirstName('Fomo')
            ->withShippingLastName('Parcel')
            ->withShippingPostcode('1000');
    }

    public function withShippingAddressInGermany(): self
    {
        return $this
            ->withShippingAddress1('Straßmannstraße 2')
            ->withShippingCity('Berlin')
            ->withShippingCompany('MyParcel DE')
            ->withShippingCountry(CountryCodes::CC_DE)
            ->withShippingFirstName('Bier')
            ->withShippingLastName('Parcel')
            ->withShippingPostcode('10249')
            ->withShippingState('DE-BE');
    }

    public function withShippingAddressInTheUsa(): self
    {
        return $this
            ->withShippingAddress1('123 Fake St')
            ->withShippingCity('New York')
            ->withShippingCompany('MyParcel US')
            ->withShippingCountry(CountryCodes::CC_US)
            ->withShippingFirstName('Abe')
            ->withShippingLastName('Lincoln')
            ->withShippingPostcode('10001')
            ->withShippingState('NY');
    }

    protected function createDefault(): FactoryInterface
    {
        return $this
            ->withBillingAddress1('Antareslaan 31')
            ->withBillingAddress2('')
            ->withBillingCity('Hoofddorp')
            ->withBillingCompany('MyParcel')
            ->withBillingCountry('NL')
            ->withBillingEmail('test@myparcel.nl')
            ->withBillingFirstName('John')
            ->withBillingLastName('Doe')
            ->withBillingPhone('0612345678')
            ->withBillingPostcode('2132 JE')
            ->withBillingState('')
            ->withCustomerNote('This is a test order')
            ->withDateCreated(new WC_DateTime('2021-01-01 18:03:41'))
            ->withShippingAddress1('Antareslaan 31')
            ->withShippingAddress2('')
            ->withShippingCity('Hoofddorp')
            ->withShippingCompany('MyParcel')
            ->withShippingCountry('NL')
            ->withShippingEmail('test@myparcel.nl')
            ->withShippingFirstName('John')
            ->withShippingLastName('Doe')
            ->withShippingPhone('0612345678')
            ->withShippingPostcode('2132 JE')
            ->withShippingState('')
            ->withStatus('pending')
            ->withItems([
                wpFactory(WC_Order_Item_Product::class)
                    ->withQuantity(2)
                    ->withTotal(1000)
                    ->withProduct(
                        wpFactory(WC_Product::class)
                            ->withId(3214)
                            ->withPrice(14.99) // euro
                            ->withWeight(0.25) // kilograms
                            ->withLength(100)
                            ->withWidth(80)
                            ->withHeight(50)
                            ->withMeta([
                                '_pest_product_country_of_origin'        => 'NL',
                                '_pest_product_customs_code'             => '1234',
                                '_pest_product_disable_delivery_options' => false,
                                '_pest_product_drop_off_delay'           => 1,
                                '_pest_product_export_age_check'         => -1,
                                '_pest_product_export_insurance'         => -1,
                                '_pest_product_export_large_format'      => -1,
                                '_pest_product_export_only_recipient'    => -1,
                                '_pest_product_export_signature'         => -1,
                                '_pest_product_fit_in_digital_stamp'     => 2,
                                '_pest_product_fit_in_mailbox'           => 4,
                                '_pest_product_package_type'             => 'mailbox',
                                '_pest_product_return_shipments'         => 0,
                            ])
                    ),
                wpFactory(WC_Order_Item::class)->withTotal(1000),
                wpFactory(WC_Order_Item_Product::class)
                    ->withQuantity(2)
                    ->withTotal(1000)
                    ->withProduct(
                        wpFactory(WC_Product::class)
                            ->withId(2324)
                            ->withName('Test digital product')
                            ->withSku('WVS-0002')
                            ->withNeedsShipping(false)
                    ),
            ]);
    }
}
