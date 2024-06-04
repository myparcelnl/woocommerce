<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use Exception;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Sdk\src\Support\Str;

class LegacyDeliveryOptionsAdapter
{
    private const SHIPPING_OPTIONS = [
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
    ];
    private const PICKUP_LOCATION  = [
        'postal_code'       => 'string',
        'street'            => 'string',
        'number'            => 'string',
        'city'              => 'string',
        'location_code'     => 'string',
        'location_name'     => 'string',
        'cc'                => 'string',
        'retail_network_id' => 'string',
    ];

    /**
     * @param  mixed $value
     *
     * @return null|bool
     */
    public function fixBool($value): ?bool
    {
        switch ((string) $value) {
            case '1':
                return true;
            case '0':
                return false;
            default:
                return null;
        }
    }

    public function fixItems(array $arr, array $model): array
    {
        $result = [];

        foreach ($model as $key => $type) {
            $value = $arr[$key] ?? $arr[Str::camel($key)] ?? null;

            if ('bool' === $type) {
                $value = $this->fixBool($value);
            } elseif ('-1' === (string) $value) {
                $value = null;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param  DeliveryOptions $deliveryOptions
     *
     * @return array
     */
    public function fromDeliveryOptions(DeliveryOptions $deliveryOptions): array
    {
        try {
            $arr = $deliveryOptions->toStorableArray();

            $arr['carrier']  = $arr['carrier']['externalIdentifier'];
            $arr['isPickup'] = 'pickup' === $arr['deliveryType'];

            if (is_string($arr['date'])) {
                $arr['date'] = substr($arr['date'], 0, 10) . 'T00:00:00.000Z';
            } else {
                $arr['date'] = null;
            }

            /**
             * To ensure backwards compatibility in consuming applications, we convert camelCase to snake_case
             * for shipmentOptions and pickupLocation. Everything else should remain camelCased.
             * In addition, the boolean values must be returned as actual booleans.
             */
            if (isset($arr['shipmentOptions']) && is_array($arr['shipmentOptions'])) {
                $arr['shipmentOptions'] = $this->fixItems($arr['shipmentOptions'], self::SHIPPING_OPTIONS);
            }

            if (isset($arr['pickupLocation']) && is_array($arr['pickupLocation'])) {
                $arr['pickupLocation'] = $this->fixItems($arr['pickupLocation'], self::PICKUP_LOCATION);
            } else {
                $arr['pickupLocation'] = null;
            }

            return $arr;
        } catch (Exception $e) {
            return [];
        }
    }
}
