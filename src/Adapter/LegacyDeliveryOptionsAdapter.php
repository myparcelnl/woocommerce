<?php
declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\DeliveryOptionsV3Adapter;
use MyParcelNL\Sdk\src\Factory\DeliveryOptionsAdapterFactory;

class LegacyDeliveryOptionsAdapter
{
    /**
     * @param DeliveryOptions $deliveryOptions
     * @return AbstractDeliveryOptionsAdapter
     */
    public function fromDeliveryOptions(DeliveryOptions $deliveryOptions): AbstractDeliveryOptionsAdapter
    {
        try {
            $arr = $deliveryOptions->toStorableArray();

            $arr['carrier'] = $arr['carrier']['externalIdentifier'];
            $arr['isPickup'] = 'pickup' === $arr['deliveryType'];

            if (isset($arr['date'])) {
                $arr['date'] = substr($arr['date'], 0, 10) . 'T00:00:00.000Z';
            }

            if (isset($arr['shipmentOptions']) && is_array($arr['shipmentOptions'])) {
                foreach ($arr['shipmentOptions'] as $key => $value) {
                    $arr['shipmentOptions'][$key] = $this->fixBool($value);
                }
            }

            return DeliveryOptionsAdapterFactory::create($arr);
        } catch (\Exception $e) {

            return new DeliveryOptionsV3Adapter([]);
        }
    }

    /**
     * @param mixed $value
     * @return bool|null
     */
    public function fixBool($value): ?bool
    {
        switch ((int)$value) {
            case 1:
                return true;
            case 0:
                return false;
            default:
                return null;
        }
    }
}
