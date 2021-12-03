<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use MyParcelNL\Sdk\src\Helper\ValidateStreet;
use WC_Order;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;

class BillingRecipientFromWCOrder extends RecipientFromWCOrder
{
    /**
     * @var WC_Order
     */
    private $order;

    /**
     * @var string
     */
    protected $local;

    /**
     * RecipientFromOrder constructor.
     *
     * @param WC_Order $order
     * @param string
     *
     * @throws \Exception
     */
    public function __construct(WC_Order $order, string $local)
    {
        $this->order = $order;
        $this->local = $local;
        parent::__construct();
    }

    /**
     * @return array
     */
    public function prepareOrderData(): array
    {
        $addressParts = $this->getAddressDetails();

        $fullStreet           = $addressParts['full_street'];
        $cc                   = $this->order->get_billing_country();
        $city                 = $this->order->get_billing_city();
        $person               = $this->getBillingName();
        $company              = $this->order->get_billing_company();
        $email                = $this->getEmailAddress($this->order);
        $phone                = $this->getPhoneNumber($this->order);
        $postalCode           = $this->order->get_billing_postcode();
        $region               = $this->order->get_billing_state();
        $streetAdditionalInfo = $addressParts['street_additional_info'];

        return [
            'cc'                     => $cc,
            'city'                   => $city,
            'person'                 => $person,
            'company'                => $company,
            'email'                  => $email,
            'phone'                  => $phone,
            'postal_code'            => $postalCode,
            'region'                 => $region,
            'street_additional_info' => $streetAdditionalInfo,
            'full_street'            => $fullStreet,
            'local'                  => $this->local,
        ];
    }

    /**
     * @return string
     */
    private function getBillingName() : string
    {
        return method_exists($this->order, 'get_formatted_billing_full_name')
            ? $this->order->get_formatted_billing_full_name()
            : trim($this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name());
    }

    /**
     * @return array
     * @throws \JsonException
     */
    public function getAddressParts() : array
    {
        return [
            'first_address_line'     => $this->order->get_billing_address_1(),
            'street'                 => WCX_Order::get_meta($this->order, '_billing_street_name'),
            'street_additional_info' => $this->order->get_billing_address_2(),
            'number'                 => WCX_Order::get_meta($this->order, '_billing_house_number') ?: '',
            'number_suffix' => WCX_Order::get_meta($this->order, '_billing_house_number_suffix') ?: '',
        ];
    }
}


