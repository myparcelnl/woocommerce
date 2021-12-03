<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Includes\Adapter;

use MyParcelNL\Sdk\src\Helper\ValidateStreet;
use MyParcelNL\Sdk\src\Model\Fulfilment\OrderLine;
use MyParcelNL\Sdk\src\Model\Recipient;
use WC_Order;
use WC_Order_Item;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;

class ShippingRecipientFromWCOrder extends RecipientFromWCOrder
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
        $cc                   = $this->order->get_shipping_country();
        $city                 = $this->order->get_shipping_city();
        $person               = $this->getShippingName();
        $company              = $this->order->get_shipping_company();
        $email                = $this->getEmailAddress($this->order);
        $phone                = $this->getPhoneNumber($this->order);
        $postalCode           = $this->order->get_shipping_postcode();
        $region               = $this->order->get_shipping_state();
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
    private function getShippingName() : string
    {
        return method_exists($this->order, 'get_formatted_shipping_full_name')
            ? $this->order->get_formatted_shipping_full_name()
            : trim($this->order->get_shipping_first_name() . ' ' . $this->order->get_shipping_last_name());
    }

    /**
     * @return array
     *
     * @throws \JsonException
     */
    public function getAddressParts() : array
    {
        return [
            'first_address_line'     => $this->order->get_shipping_address_1(),
            'street'                 => WCX_Order::get_meta($this->order, '_shipping_street_name'),
            'street_additional_info' => $this->order->get_shipping_address_2(),
            'number'                 => WCX_Order::get_meta($this->order, '_shipping_house_number') ?: '',
            'number_suffix'          => WCX_Order::get_meta($this->order, '_shipping_house_number_suffix') ?: '',
        ];
    }
}
