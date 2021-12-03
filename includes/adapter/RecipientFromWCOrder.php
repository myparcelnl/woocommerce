<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use MyParcelNL\Sdk\src\Helper\ValidateStreet;
use MyParcelNL\Sdk\src\Model\Recipient;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;

abstract class RecipientFromWCOrder extends Recipient
{
    public const SUFFIX_CHECK_REG = "~^([a-z]{1}\d{1,3}|-\d{1,4}\d{2}\w{1,2}|[a-z]{1}[a-z\s]{0,3})(?:\W|$)~i";

    /**
     * RecipientFromWCOrder constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $recipientDetails = $this->prepareOrderData();
        parent::__construct($recipientDetails, $this->local);
    }

    abstract public function prepareOrderData(): array;

    abstract public function getAddressParts();

    /**
     * @param  $order
     *
     * @return string|null
     */
    public function getEmailAddress($order): ?string
    {
        $deliveryOptions = WCX_Order::get_meta($order, WCMYPA_Admin::META_DELIVERY_OPTIONS);
        $emailConnected  = WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_CONNECT_EMAIL);

        return $emailConnected || $deliveryOptions['isPickup']
            ? $order->get_billing_email()
            : '';
    }

    /**
     * @param  $order
     *
     * @return string|null
     */
    public function getPhoneNumber($order): ?string
    {
        $connectPhone = WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_CONNECT_PHONE);

        return $connectPhone
            ? $order->get_billing_phone()
            : '';
    }

    /**
     * @return array
     */
    public function getAddressDetails(): array
    {
        $addressParts = $this->getAddressParts();
        $separateParts = $this->separateStreet($addressParts['first_address_line']);

        if (!$separateParts['number_suffix'] && $this->isSuffix($addressParts['street_additional_info'])) {
            $addressParts['number_suffix'] = $addressParts['street_additional_info'];
            $addressParts['street_additional_info'] = '';
        }

        if (!$addressParts['street']) {
            $addressParts['street'] = $addressParts['first_address_line'];
        }

        $addressParts['full_street'] = implode(' ',
            [
                $addressParts['street'],
                $addressParts['number'],
                $addressParts['number_suffix'],
                $addressParts['box_number'],
            ]
        );

        return $addressParts;
    }

    /**
     * @param  string|null $street
     *
     * @return array
     */
    public function separateStreet(?string $street): array
    {
        preg_match(ValidateStreet::SPLIT_STREET_REGEX_BE, $street, $separateStreet);

        return $separateStreet;
    }

    /**
     * @param  string|null $additionalInfo
     *
     * @return bool
     */
    public function isSuffix(?string $additionalInfo): bool
    {
        return (bool) preg_match(self::SUFFIX_CHECK_REG, $additionalInfo);
    }
}
