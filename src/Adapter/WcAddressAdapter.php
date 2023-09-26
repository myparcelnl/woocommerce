<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Pdk;
use WC_Cart;
use WC_Customer;
use WC_Order;

class WcAddressAdapter
{
    /**
     * @param  \WC_Cart    $cart
     * @param  null|string $addressType
     *
     * @return array
     */
    public function fromWcCart(WC_Cart $cart, ?string $addressType = null): array
    {
        return $this->fromWcCustomer($cart->get_customer(), $addressType);
    }

    /**
     * @param  \WC_Customer $customer
     * @param  null|string  $addressType
     *
     * @return array
     */
    public function fromWcCustomer(WC_Customer $customer, ?string $addressType = null): array
    {
        return $this->getAddressFields($customer, $this->resolveAddressType($customer, $addressType));
    }

    /**
     * @param  \WC_Order   $order
     * @param  null|string $addressType
     *
     * @return array
     * @throws \Exception
     */
    public function fromWcOrder(WC_Order $order, ?string $addressType = null): array
    {
        $resolvedAddressType = $this->resolveAddressType($order, $addressType);

        return array_merge(
            $this->getAddressFields($order, $resolvedAddressType),
            $this->getSeparateAddressFromOrder($order, $resolvedAddressType),
            [
                'eoriNumber' => $this->getOrderMeta($order, Pdk::get('fieldEoriNumber'), $resolvedAddressType),
                'vatNumber'  => $this->getOrderMeta($order, Pdk::get('fieldVatNumber'), $resolvedAddressType),
            ]
        );
    }

    /**
     * @param  \WC_Order|\WC_Customer $order
     * @param  string                 $field
     * @param  string                 $addressType
     *
     * @return mixed
     */
    private function getAddressField($order, string $field, string $addressType)
    {
        $method = implode('_', ['get', $addressType, $field]);

        return $order->{$method}();
    }

    /**
     * @param  \WC_Customer|\WC_Order $class
     * @param  string                 $addressType
     *
     * @return array
     */
    private function getAddressFields($class, string $addressType): array
    {
        $state = $this->getState($class, $addressType);

        return [
            'email' => $class->get_billing_email(),
            'phone' => $class->get_billing_phone(),

            'person' => $this->getPerson($class, $addressType),

            'address1'   => $this->getAddressField($class, Pdk::get('fieldAddress1'), $addressType),
            'address2'   => $this->getAddressField($class, Pdk::get('fieldAddress2'), $addressType),
            'cc'         => $this->getAddressField($class, Pdk::get('fieldCountry'), $addressType),
            'city'       => $this->getAddressField($class, Pdk::get('fieldCity'), $addressType),
            'company'    => $this->getAddressField($class, Pdk::get('fieldCompany'), $addressType),
            'postalCode' => $this->getAddressField($class, Pdk::get('fieldPostalCode'), $addressType),
            'region'     => $state,
            'state'      => $state,
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
     * @param  \WC_Order|WC_Customer $instance
     * @param  string                $addressType
     *
     * @return string
     */
    private function getPerson($instance, string $addressType): string
    {
        $getFullName = "get_formatted_{$addressType}_full_name";

        if (method_exists($instance, $getFullName)) {
            return $instance->{$getFullName}();
        }

        return trim(
            implode(' ', [
                $this->getAddressField($instance, Pdk::get('fieldFirstName'), $addressType),
                $this->getAddressField($instance, Pdk::get('fieldLastName'), $addressType),
            ])
        );
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $addressType
     *
     * @return array
     */
    private function getSeparateAddressFromOrder(WC_Order $order, string $addressType): array
    {
        $street       = $this->getOrderMeta($order, Pdk::get('fieldStreet'), $addressType);
        $number       = $this->getOrderMeta($order, Pdk::get('fieldNumber'), $addressType);
        $numberSuffix = $this->getOrderMeta($order, Pdk::get('fieldNumberSuffix'), $addressType);

        $country = $this->getAddressField($order, Pdk::get('fieldCountry'), $addressType);

        $hasSeparateAddress = $street || $number || $numberSuffix;

        return $hasSeparateAddress && in_array($country, Pdk::get('countriesWithSeparateAddressFields'), true)
            ? ['fullStreet' => trim("$street $number $numberSuffix")]
            : [];
    }

    /**
     * @param  \WC_Customer|\WC_Order $class
     * @param  string                 $addressType
     *
     * @return string
     */
    private function getState($class, string $addressType): string
    {
        $value = $this->getAddressField($class, Pdk::get('fieldState'), $addressType);

        return $value ? Arr::last(explode('-', $value)) : '';
    }

    /**
     * @param  WC_Order|WC_Customer $object
     * @param  null|string          $addressType
     *
     * @return string
     */
    private function resolveAddressType($object, ?string $addressType): string
    {
        return $addressType ?? ($object->has_shipping_address()
            ? Pdk::get('wcAddressTypeShipping')
            : Pdk::get('wcAddressTypeBilling'));
    }
}
