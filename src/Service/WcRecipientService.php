<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Facade\Pdk;
use WC_Order;

class WcRecipientService
{
    /**
     * @var \MyParcelNL\Pdk\Base\Service\CountryService
     */
    private $countryService;

    /**
     * @param  \MyParcelNL\Pdk\Base\Service\CountryService $countryService
     */
    public function __construct(CountryService $countryService)
    {
        $this->countryService = $countryService;
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $addressType
     *
     * @return array
     */
    public function createAddress(WC_Order $order, string $addressType): array
    {
        return [
                'person' => $this->getPersonFromOrder($order, $addressType),

                'cc'         => $this->getOrderValue($order, Pdk::get('fieldCountry'), $addressType),
                'city'       => $this->getOrderValue($order, Pdk::get('fieldCity'), $addressType),
                'company'    => $this->getOrderValue($order, Pdk::get('fieldCompany'), $addressType),
                'postalCode' => $this->getOrderValue($order, Pdk::get('fieldPostalCode'), $addressType),
                'region'     => $this->getOrderValue($order, Pdk::get('fieldRegion'), $addressType),

                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),

                'eoriNumber' => $this->getOrderMeta($order, Pdk::get('fieldEoriNumber'), $addressType),
                'vatNumber'  => $this->getOrderMeta($order, Pdk::get('fieldVatNumber'), $addressType),
            ] + $this->getAddressFromOrder($order, $addressType);
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $addressType
     *
     * @return array
     */
    private function getAddressFromOrder(WC_Order $order, string $addressType): array
    {
        $street       = $this->getOrderMeta($order, Pdk::get('fieldStreet'), $addressType);
        $number       = $this->getOrderMeta($order, Pdk::get('fieldNumber'), $addressType);
        $numberSuffix = $this->getOrderMeta($order, Pdk::get('fieldNumberSuffix'), $addressType);

        $cc = $this->getOrderValue($order, Pdk::get('fieldCountry'), $addressType);

        $hasSeparateAddress = $street || $number || $numberSuffix;

        if ($hasSeparateAddress && $this->countryService->isUnique($cc)) {
            return [
                'street'       => $street,
                'number'       => $number,
                'numberSuffix' => $numberSuffix,
            ];
        }

        return [
            'fullStreet'           => $this->getOrderValue($order, Pdk::get('fieldAddress1'), $addressType),
            'streetAdditionalInfo' => $this->getOrderValue($order, Pdk::get('fieldAddress2'), $addressType),
        ];
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $field
     * @param  string    $addressType
     *
     * @return mixed|null
     */
    private function getOrderMeta(WC_Order $order, string $field, string $addressType)
    {
        $metaKey = sprintf('_%s_%s', $addressType, $field);

        return $order->get_meta($metaKey) ?: null;
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $field
     * @param  string    $addressType
     *
     * @return mixed
     */
    private function getOrderValue(WC_Order $order, string $field, string $addressType)
    {
        $method = implode('_', ['get', $addressType, $field]);

        return $order->{$method}();
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $addressType
     *
     * @return string
     */
    private function getPersonFromOrder(WC_Order $order, string $addressType): string
    {
        $getFullName = "get_formatted_{$addressType}_full_name";

        if (method_exists($order, $getFullName)) {
            return $order->{$getFullName}();
        }

        return trim(
            implode(' ', [
                $this->getOrderValue($order, Pdk::get('fieldFirstName'), $addressType),
                $this->getOrderValue($order, Pdk::get('fieldLastName'), $addressType),
            ])
        );
    }
}
