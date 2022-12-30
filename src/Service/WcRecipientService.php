<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Sdk\src\Helper\SplitStreet;
use MyParcelNL\Sdk\src\Helper\ValidateStreet;
use WC_Order;

class WcRecipientService
{
    public const  BILLING                           = 'billing';
    public const  SHIPPING                          = 'shipping';
    private const MIN_STREET_ADDITIONAL_INFO_LENGTH = 10;
    //    /**
    //     * Parameter $type should always be one of two constants, either 'billing' or 'shipping'.
    //     *
    //     * @param  \WC_Order $order
    //     * @param  string    $originCountry
    //     * @param  string    $type
    //     *
    //     * @throws \Exception
    //     */
    //    public function __construct(WC_Order $order, string $originCountry, string $type)
    //    {
    //        $recipientDetails = $this->createAddress($order, $type);
    //        parent::__construct($recipientDetails, $originCountry);
    //    }

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
     * @param  bool  $isNL
     * @param  array $streetParts
     *
     * @return string
     */
    private function createFullStreet(bool $isNL, array $streetParts): string
    {
        return $isNL
            ? implode(' ', [
                    $streetParts['street'] ?? null,
                    $streetParts['number'] ?? null,
                    $streetParts['number_suffix'] ?? null,
                ]
            )
            : implode(' ', [
                    $streetParts['street'] ?? null,
                    $streetParts['number'] ?? null,
                    $streetParts['box_separator'] ?? null,
                    $streetParts['box_number'],
                ]
            );
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
        $addressLine2 = $order->{"get_{$type}_address_2"}();
        $addressLine1 = $order->{"get_{$type}_address_1"}();
        $country      = $order->{"get_{$type}_country"}();
        $isNL         = CountryService::CC_NL === $country;
        $isBE         = CountryService::CC_BE === $country;

        $isUsingSplitAddressFields = $street || $number || $numberSuffix;

        if (! $isNL && ! $isBE) {
            $fullStreet = $isUsingSplitAddressFields
                ? implode(' ', [$street, $number, $numberSuffix])
                : $addressLine1;

            return [
                'full_street'            => $fullStreet,
                'street_additional_info' => $addressLine2 ?? null,
            ];
        }

        $streetParts = $this->separateStreet($addressLine1, $order, $type);

        if (! $streetParts) {
            $streetParts['street'] = $addressLine1;
        }

        $addressLine2IsNumberSuffix = strlen($addressLine2) < self::MIN_STREET_ADDITIONAL_INFO_LENGTH;

        if (! isset($streetParts['number_suffix']) && $addressLine2IsNumberSuffix) {
            $streetParts['number_suffix'] = $addressLine2;
            $addressLine2                 = null;
        }

        if ($isUsingSplitAddressFields) {
            $streetParts['street']        = $street ?? $streetParts['street'] ?? null;
            $streetParts['number']        = $number ?? $streetParts['number'] ?? null;
            $streetParts['number_suffix'] = $numberSuffix ?? $streetParts['number_suffix'] ?? null;
        }

        if (is_int($streetParts['number_suffix'])) {
            /** Two dashes results in address errors, 'abs' makes sure there can only be one */
            $streetParts['number_suffix'] = sprintf(' -%d', abs($streetParts['number_suffix']));
        }

        return [
            'full_street'            => $this->createFullStreet($isNL, $streetParts),
            'street_additional_info' => $addressLine2,
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
        $getFullName  = "get_formatted_{$type}_full_name";
        $getFirstName = "get_{$type}_first_name";
        $getLastName  = "get_{$type}_last_name";

        return method_exists($order, $getFullName)
            ? $order->{$getFullName}()
            : trim($order->{$getFirstName}() . ' WcRecipientService.php' . $order->{$getLastName}());
    }

    /**
     * @param  string    $street
     * @param  \WC_Order $order
     * @param  string    $type
     *
     * @return array
     */
    private function separateStreet(string $street, WC_Order $order, string $type): array
    {
        if ($order->{"get_{$type}_country"}() === CountryService::CC_BE) {
            foreach (SplitStreet::BOX_SEPARATOR_BY_REGEX as $boxRegex) {
                $street = preg_replace('#' . $boxRegex . '([0-9])#', SplitStreet::BOX_NL . ' ' . ltrim('$1'), $street);
            }

            preg_match(ValidateStreet::SPLIT_STREET_REGEX_BE, $street, $separateStreet);

            return $separateStreet;
        }

        preg_match(ValidateStreet::SPLIT_STREET_REGEX_NL, $street, $separateStreet);

        return $separateStreet;
    }
}