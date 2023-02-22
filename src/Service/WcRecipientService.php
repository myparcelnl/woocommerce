<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Base\Service\CountryService;
use WC_Order;

class WcRecipientService
{
    public const BILLING  = 'billing';
    public const SHIPPING = 'shipping';

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
     * @param  string    $type
     *
     * @return array
     * @throws \JsonException
     * @throws \Exception
     */
    public function createAddress(WC_Order $order, string $type): array
    {
        return [
                'cc'          => $order->{"get_{$type}_country"}(),
                'city'        => $order->{"get_{$type}_city"}(),
                'company'     => $order->{"get_{$type}_company"}(),
                'postal_code' => $order->{"get_{$type}_postcode"}(),
                'region'      => $order->{"get_{$type}_state"}(),
                'person'      => $this->getPersonFromOrder($order, $type),
                'email'       => $order->get_billing_email(),
                'phone'       => $order->get_billing_phone(),
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
        $street       = $order->get_meta("_{$type}_street_name") ?: null;
        $number       = $order->get_meta("_{$type}_house_number") ?: null;
        $numberSuffix = $order->get_meta("_{$type}_house_number_suffix") ?: null;

        $cc = $order->{"get_{$type}_country"}();

        $isUnique        = $this->countryService->isUnique($cc);
        $hasSplitAddress = $street || $number || $numberSuffix;

        if ($isUnique && $hasSplitAddress) {
            return [
                'street'        => $street,
                'number'        => $number,
                'number_suffix' => $numberSuffix,
            ];
        }

        return [
            'full_street'            => $order->{"get_{$type}_address_1"}(),
            'street_additional_info' => $order->{"get_{$type}_address_2"}() ?: null,
        ];
    }

    /**
     * @param  \WC_Order $order
     * @param  string    $type
     *
     * @return string
     */
    private function getPersonFromOrder(WC_Order $order, string $type): string
    {
        $getFullName = "get_formatted_{$type}_full_name";

        return method_exists($order, $getFullName)
            ? $order->{$getFullName}()
            : trim(
                implode(' ', [
                    $order->{"get_{$type}_first_name"}(),
                    $order->{"get_{$type}_last_name"}(),
                ])
            );
    }
}
