<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use MyParcelNL\Sdk\src\Model\Recipient;
use WC_Order;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;

class RecipientFromWCOrder extends Recipient
{
    public const BILLING  = 'billing';
    public const SHIPPING = 'shipping';

    /**
     * Parameter $type should always be one of two constants, either 'billing' or 'shipping'.
     *
     * @param  \WC_Order $order
     * @param  string   $originCountry
     * @param  string   $type
     *
     * @throws \Exception
     */
    public function __construct(WC_Order $order, string $originCountry, string $type)
    {
        $recipientDetails = $this->createAddress($order, $type);
        parent::__construct($recipientDetails, $originCountry);
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $type
     *
     * @return array
     * @throws \JsonException
     */
    private function createAddress(WC_Order $order, string $type): array
    {
        return [
                'cc'          => $order->{"get_{$type}_country"}(),
                'city'        => $order->{"get_{$type}_city"}(),
                'company'     => $order->{"get_{$type}_company"}(),
                'postal_code' => $order->{"get_{$type}_postcode"}(),
                'region'      => $order->{"get_{$type}_state"}(),
                'person'      => $this->getPersonFromOrder($order, $type),
                'email'       => $this->getEmailAddressFromOrder($order),
                'phone'       => $this->getPhoneNumberFromOrder($order),
            ] + $this->getAddressFromOrder($order, $type);
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $type
     *
     * @return array
     * @throws \JsonException
     */
    private function getAddressFromOrder(WC_Order $order, string $type): array
    {
        $street       = WCX_Order::get_meta($order, "_{$type}_street_name") ?: null;
        $number       = WCX_Order::get_meta($order, "_{$type}_house_number") ?: null;
        $numberSuffix = WCX_Order::get_meta($order, "_{$type}_house_number_suffix") ?: null;

        $isUsingSplitAddressFields = ! empty($street) || ! empty($number) || ! empty($numberSuffix);

        if ($isUsingSplitAddressFields) {
            $fullStreet           = implode(' ', [$street, $number, $numberSuffix]);
            $streetAdditionalInfo = null;
        } else {
            $fullStreet           = $order->get_shipping_address_1();
            $streetAdditionalInfo = $order->get_shipping_address_2();
        }

        return [
            'full_street'            => $fullStreet,
            'street_additional_info' => $streetAdditionalInfo,
        ];
    }

    /**
     * Email address should always come from the billing address.
     *
     * @param  \WC_Order $order
     *
     * @return string|null
     * @throws \JsonException
     */
    private function getEmailAddressFromOrder(WC_Order $order): ?string
    {
        $deliveryOptions = WCX_Order::get_meta($order, WCMYPA_Admin::META_DELIVERY_OPTIONS);
        $emailConnected  = WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_CONNECT_EMAIL);

        return $emailConnected || $deliveryOptions['isPickup']
            ? $order->get_billing_email()
            : null;
    }

    /**
     * Phone should always come from the billing address.
     *
     * @param  \WC_Order $order
     *
     * @return string|null
     */
    private function getPhoneNumberFromOrder(WC_Order $order): ?string
    {
        $connectPhone = WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_CONNECT_PHONE);

        return $connectPhone
            ? $order->get_billing_phone()
            : null;
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $type
     *
     * @return string
     */
    private function getPersonFromOrder(WC_Order $order, string $type): string
    {
        $getFullName  = "get_formatted_{$type}_full_name";
        $getFirstName = "get_{$type}_first_name";
        $getLastName  = "get_{$type}_last_name";

        return method_exists($order, $getFullName)
            ? $order->{$getFullName}()
            : trim($order->{$getFirstName}() . ' ' . $order->{$getLastName}());
    }
}
