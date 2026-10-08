<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use MyParcelNL\Sdk\Client\Generated\CoreApi\Model\RefTypesDeliveryTypeV2;
use MyParcelNL\Sdk\Services\Mapping\ApiMapperService;
use MyParcelNL\Sdk\Support\Str;

class LegacyDeliveryOptionsAdapter
{
    private const LEGACY_OPTIONS = [
        'shipmentOptions' => [
            'signature'         => 'bool',
            'insurance'         => 'int',
            'age_check'         => 'bool',
            'only_recipient'    => 'bool',
            'return'            => 'bool',
            'same_day_delivery' => 'bool',
            'large_format'      => 'bool',
            'label_description' => 'string',
            'hide_sender'       => 'bool',
            'extra_assurance'   => 'bool',
        ],
        'pickupLocation'  => [
            'postal_code'       => 'string',
            'street'            => 'string',
            'number'            => 'string',
            'city'              => 'string',
            'location_code'     => 'string',
            'location_name'     => 'string',
            'cc'                => 'string',
            'retail_network_id' => 'string',
        ],
    ];

    /**
     * @param  DeliveryOptions $deliveryOptions
     *
     * @return array
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function fromDeliveryOptions(DeliveryOptions $deliveryOptions): array
    {
        $arr = $deliveryOptions->toStorableArray();

        $carrier = $arr['carrier'] ?? null;

        if (is_array($carrier)) {
            $carrierName = $carrier['externalIdentifier'] ?? ($carrier['carrier'] ?? null);
        } elseif (is_string($carrier)) {
            $carrierName = ApiMapperService::forCarrier()->legacyNameFromV2Name($carrier) ?? $carrier;
        } else {
            return [];
        }

        if (! is_string($carrierName)) {
            return [];
        }

        $arr['carrier'] = $carrierName;
        $arr['isPickup'] = RefTypesDeliveryTypeV2::PICKUP
            === ApiMapperService::forDeliveryType()->v2NameFromLegacyName((string) $arr['deliveryType']);

        if (isset($arr['date']) && is_string($arr['date'])) {
            $arr['date'] = substr($arr['date'], 0, 10) . 'T00:00:00.000Z';
        } else {
            $arr['date'] = null;
        }

        /**
         * To ensure backwards compatibility in consuming applications, we convert camelCase to snake_case
         * for shipmentOptions and pickupLocation. Everything else should remain camelCased.
         */
        foreach (self::LEGACY_OPTIONS as $item => $model) {
            if (isset($arr[$item]) && is_array($arr[$item])) {
                $arr[$item] = $this->fixOptions($arr[$item], $model);
            } else {
                $arr[$item] = null;
            }
        }

        return $arr;
    }

    /**
     * @param  mixed $value
     *
     * @return null|bool
     */
    private function fixBool($value): ?bool
    {
        switch ((string) $value) {
            case (string) TriStateService::ENABLED:
                return true;
            case (string) TriStateService::DISABLED:
                return false;
            default:
                return null;
        }
    }

    /**
     * @param  array $arr
     * @param  array $model
     *
     * @return array
     */
    private function fixOptions(array $arr, array $model): array
    {
        $result = [];

        foreach ($model as $key => $type) {
            $value = $arr[$key] ?? $arr[Str::camel($key)] ?? null;

            /**
             * Boolean values must be returned as actual boolean for legacy values.
             * INHERIT ('-1') is converted to null (not set).
             */
            if ('bool' === $type) {
                $value = $this->fixBool($value);
            } elseif (((string) TriStateService::INHERIT) === (string) $value) {
                $value = null;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
