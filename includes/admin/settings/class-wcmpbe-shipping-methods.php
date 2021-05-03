<?php

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Creates the array of available shipping methods in the checkout.
 */
class WCMPBE_Shipping_Methods
{
    public const FLAT_RATE                   = 'flat_rate';
    public const FLEXIBLE_SHIPPING           = 'flexible_shipping';
    public const FLEXIBLE_SHIPPING_INFO      = 'flexible_shipping_info';
    public const FREE_SHIPPING               = 'free_shipping';
    public const LEGACY_FLAT_RATE            = 'legacy_flat_rate';
    public const LOCAL_PICKUP                = 'local_pickup';
    public const TABLE_RATES_BOLDER_ELEMENTS = 'betrs_shipping';
    public const TABLE_RATES_WOOCOMMERCE     = 'table_rate';

    private const SHIPPING_METHOD_CLASS_WOOCOMMERCE      = 'WC_Shipping_Table_Rate';
    private const SHIPPING_METHOD_CLASS_BOLDER_ELEMENTS  = 'BE_Table_Rate_Method';
    private const SHIPPING_METHOD_CLASS_WP_DESK_FLEXIBLE = 'WPDesk_Flexible_Shipping';

    /**
     * Items in this array will not be added to the shipping methods array. Useful for table rates because the base
     * method "table_rate" for example, can't be selected. Only the suffixed ones "table_rate:1:3" can, so it
     * shouldn't be in this array.
     */
    private const DISALLOWED_SHIPPING_METHODS = [
        self::FLEXIBLE_SHIPPING,
        self::FLEXIBLE_SHIPPING_INFO,
        self::TABLE_RATES_BOLDER_ELEMENTS,
        self::TABLE_RATES_WOOCOMMERCE,
    ];

    /**
     * @var array
     */
    private $shippingMethods = [];

    public function __construct()
    {
        $this->gatherShippingMethods();
    }

    /**
     * @return array
     */
    public function getShippingMethods(): array
    {
        return $this->shippingMethods;
    }

    private function gatherShippingMethods(): void
    {
        $wooCommerceShippingMethods = WC()->shipping()->get_shipping_methods();

        if (! $wooCommerceShippingMethods) {
            return;
        }

        foreach ($wooCommerceShippingMethods as $shippingMethodId => $zoneShippingMethod) {
            $method      = $zoneShippingMethod;
            $methodTitle = $method->method_title ?? $method->title;

            $this->addShippingMethod($shippingMethodId, $methodTitle);
            $this->addFlatRateShippingMethods($shippingMethodId, $methodTitle);
            $this->addShippingMethodsFromShippingZones();
        }
    }

    /**
     * Add a shipping method. Skips disallowed entries.
     *
     * @param string $key
     * @param string $value
     */
    private function addShippingMethod(string $key, string $value): void
    {
        if (in_array($key, self::DISALLOWED_SHIPPING_METHODS)) {
            return;
        }

        $this->shippingMethods[$key] = $value;
    }

    private function addShippingMethodsFromShippingZones(): void
    {
        $shippingZones = WC_Shipping_Zones::get_zones();

        foreach ($shippingZones as $shippingZone) {
            $zoneId = $shippingZone['id'] ?? $shippingZone['zone_id'];

            if (! $zoneId) {
                continue;
            }

            $zone = WC_Shipping_Zones::get_zone($zoneId);
            /* @var WC_Shipping_Method[] $zoneShippingMethods */
            $zoneShippingMethods = $zone->get_shipping_methods();

            foreach ($zoneShippingMethods as $zoneShippingMethod) {
                $this->addZoneShippingMethodRates($zone, $zoneShippingMethod);
            }
        }
    }

    /**
     * @param string $shippingMethodId
     * @param string $methodTitle
     */
    private function addFlatRateShippingMethods(string $shippingMethodId, string $methodTitle): void
    {
        $isFlatRate      = in_array($shippingMethodId, [self::FLAT_RATE, self::LEGACY_FLAT_RATE]);
        $versionAbove2_4 = version_compare(WOOCOMMERCE_VERSION, '2.4', '>=');

        // split flat rate by shipping class
        if (! $isFlatRate || ! $versionAbove2_4) {
            return;
        }

        /** @var WP_Term[] $shippingClasses */
        $shippingClasses = WC()->shipping()->get_shipping_classes();

        foreach ($shippingClasses as $shippingClass) {
            if (! isset($shippingClass->term_id)) {
                continue;
            }

            $this->addShippingMethod(
                esc_attr($shippingMethodId) . ":" . $shippingClass->term_id,
                esc_html("{$methodTitle} - {$shippingClass->name}")
            );
        }
    }

    /**
     * @param \WC_Shipping_Zone   $zone
     * @param \WC_Shipping_Method $zoneShippingMethod
     */
    private function addZoneShippingMethodRates(WC_Shipping_Zone $zone, WC_Shipping_Method $zoneShippingMethod): void
    {
        switch (get_class($zoneShippingMethod)) {
            case self::SHIPPING_METHOD_CLASS_WOOCOMMERCE:
                $this->addWooCommerceZoneShippingMethodRates($zone, $zoneShippingMethod);
                break;
            case self::SHIPPING_METHOD_CLASS_BOLDER_ELEMENTS:
                $this->addBolderElementsZoneShippingMethodRates($zone, $zoneShippingMethod);
                break;
            case self::SHIPPING_METHOD_CLASS_WP_DESK_FLEXIBLE:
                $this->addWPDeskFlexibleZoneShippingMethodRates($zoneShippingMethod);
                break;
        }
    }

    /**
     * @param WC_Shipping_Zone   $zone
     * @param WC_Shipping_Method $zoneShippingMethod
     */
    private function addWooCommerceZoneShippingMethodRates(
        WC_Shipping_Zone $zone,
        WC_Shipping_Method $zoneShippingMethod
    ): void {
        foreach ($zoneShippingMethod->get_shipping_rates() as $zoneShippingRate) {
            $label = $zoneShippingRate->rate_label ?? "{$zoneShippingMethod->title} ({$zoneShippingRate->rate_id})";

            $this->addShippingMethod(
                self::TABLE_RATES_WOOCOMMERCE . ":{$zoneShippingMethod->instance_id}:{$zoneShippingRate->rate_id}",
                "{$zone->get_zone_name()} - {$label}"
            );
        }
    }

    /**
     * @param WC_Shipping_Zone   $zone
     * @param WC_Shipping_Method $zoneShippingMethod
     */
    private function addBolderElementsZoneShippingMethodRates(
        WC_Shipping_Zone $zone,
        WC_Shipping_Method $zoneShippingMethod
    ): void {
        $shippingMethodOption = get_option($zoneShippingMethod->id . '_options-' . $zoneShippingMethod->instance_id);

        if (! isset($shippingMethodOption['settings'])) {
            return;
        }

        foreach ($shippingMethodOption['settings'] as $zoneShippingRate) {
            $optionId = $zoneShippingRate['option_id'];
            $label    = $zoneShippingRate['title'] ?? "{$zoneShippingMethod->title} ({$optionId})";

            /*
             * It appears that after version 4.0.0 the separator changed from "_" to ":". We're adding both variants
             * here for compatibility.
             */
            foreach ([':', '_'] as $separator) {
                $this->addShippingMethod(
                    self::TABLE_RATES_BOLDER_ELEMENTS . "$separator{$zoneShippingMethod->instance_id}-{$optionId}",
                    "{$zone->get_zone_name()} - {$label}"
                );
            }
        }
    }

    /**
     * @param \WC_Shipping_Method $zoneShippingMethod
     */
    private function addWPDeskFlexibleZoneShippingMethodRates(WC_Shipping_Method $zoneShippingMethod): void
    {
        $shippingMethodOption = get_option($zoneShippingMethod->shipping_methods_option);

        if (! $shippingMethodOption) {
            return;
        }

        foreach ($shippingMethodOption as $item) {
            $this->addShippingMethod($item['id_for_shipping'], $item['method_title']);
        }
    }
}
