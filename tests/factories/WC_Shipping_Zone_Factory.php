<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;

/**
 * @template T of \WC_Shipping_Zone
 * @method $this withId(int $id)
 * @method $this withData(array $data)
 * @extends \MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory<T>
 */
class WC_Shipping_Zone_Factory extends AbstractWcDataFactory
{
    private $data = [
        'zone_name'      => '',
        'zone_order'     => 0,
        'zone_locations' => [],
    ];

    public function getClass(): string
    {
        return WC_Shipping_Zone::class;
    }

    /**
     * @param  array $zoneLocations
     *
     * @return $this
     */
    public function withZoneLocations(array $zoneLocations): self
    {
        $this->data['zone_locations'] = $zoneLocations;

        return $this->withData($this->data);
    }

    /**
     * @param  string $zoneName
     *
     * @return $this
     */
    public function withZoneName(string $zoneName): self
    {
        $this->data['zone_name'] = $zoneName;

        return $this->withData($this->data);
    }

    /**
     * @param  int $zoneOrder
     *
     * @return $this
     */
    public function withZoneOrder(int $zoneOrder): self
    {
        $this->data['zone_order'] = $zoneOrder;

        return $this->withData($this->data);
    }
}
