<?php

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter as DeliveryOptions;
use MyParcelNL\Sdk\src\Factory\DeliveryOptionsAdapterFactory;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMPBE_Cart_Fees')) {
    return;
}

/**
 * Frontend views
 */
class WCMPBE_Cart_Fees
{
    /**
     * @var array
     */
    private $fees;

    /**
     * @var DeliveryOptions
     */
    private $deliveryOptions;

    public function __construct()
    {
        // Delivery options fees
        add_action('woocommerce_cart_calculate_fees', [$this, 'get_delivery_options_fees'], 20);
    }

    /**
     * Get delivery fee in your order overview, at the front of the website
     *
     * @param WC_Cart $cart
     *
     * @throws Exception
     */
    public function get_delivery_options_fees(WC_Cart $cart): void
    {
        if (! defined('DOING_AJAX') && is_admin()) {
            return;
        }

        if (isset($_POST['post_data'])) {
            // non-default post data for AJAX calls
            parse_str($_POST['post_data'], $postData);
        } else {
            // checkout finalization
            $postData = $_POST;
        }

        // Check if delivery options exist at all.
        if (empty($postData[WCMYPABE_Admin::META_DELIVERY_OPTIONS])) {
            return;
        }

        $deliveryOptionsData = $postData[WCMYPABE_Admin::META_DELIVERY_OPTIONS];
        $deliveryOptionsData = json_decode(stripslashes($deliveryOptionsData), true);

        /*
         * Check if delivery options is null. Happens when when switching to a shipping method that does not allow
         * showing delivery options, for example.
         */
        if (!$deliveryOptionsData) {
            return;
        }

        $this->deliveryOptions = DeliveryOptionsAdapterFactory::create($deliveryOptionsData);

        $this->addDeliveryFee();
        $this->addShipmentOptionFees();

        $this->addFees($cart);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    private function getFee(string $name): array
    {
        $fees   = $this->getFeeMap();
        $titles = $this->getFeeTitles();

        return [$titles[$name] ?? null, $fees[$name] ?? 0];
    }

    /**
     * Get shipping tax class
     * adapted from WC_Tax::get_shipping_tax_rates
     * assumes per order shipping (per item shipping not supported for myparcel yet)
     *
     * @return string tax class
     */
    public function get_shipping_tax_class()
    {
        $shipping_tax_class = get_option('woocommerce_shipping_tax_class');

        // WC3.0+ sets 'inherit' for taxes based on items, empty for 'standard'
        if (version_compare(WOOCOMMERCE_VERSION, '3.0', '>=') && 'inherit' !== $shipping_tax_class) {
            $shipping_tax_class = '' === $shipping_tax_class ? 'standard' : $shipping_tax_class;

            return $shipping_tax_class;
        } elseif (! empty($shipping_tax_class) && 'inherit' !== $shipping_tax_class) {
            return $shipping_tax_class;
        }

        if ($shipping_tax_class == 'inherit') {
            $shipping_tax_class = '';
        }

        // See if we have an explicitly set shipping tax class
        if ($shipping_tax_class) {
            $tax_class = 'standard' === $shipping_tax_class ? '' : $shipping_tax_class;
        }

        $location = WC_Tax::get_tax_location('');

        if (sizeof($location) === 4) {
            list($country, $state, $postcode, $city) = $location;

            // This will be per order shipping - loop through the order and find the highest tax class rate
            $cart_tax_classes = WC()->cart->get_cart_item_tax_classes();
            // If multiple classes are found, use the first one. Don't bother with standard rate, we can get that later.
            if (sizeof($cart_tax_classes) > 1 && ! in_array('', $cart_tax_classes)) {
                $tax_classes = WC_Tax::get_tax_classes();

                foreach ($tax_classes as $tax_class) {
                    $tax_class = sanitize_title($tax_class);
                    if (in_array($tax_class, $cart_tax_classes)) {
                        // correct $tax_class is now set
                        break;
                    }
                }
                // If a single tax class is found, use it
            } elseif (sizeof($cart_tax_classes) == 1) {
                $tax_class = array_pop($cart_tax_classes);
            }

            // no rate = standard rate
            if (empty($tax_class)) {
                $tax_class = 'standard';
            }
        }

        return $tax_class;
    }

    /**
     * Add the fees for the selected shipment options.
     */
    private function addShipmentOptionFees(): void
    {
        $shipmentOptions = ($this->deliveryOptions->getShipmentOptions())->toArray();

        foreach ($shipmentOptions as $shipmentOption => $enabled) {
            //Don't add the fee if it wasn't selected.
            if ($enabled) {
                $this->addFee($shipmentOption);
            }
        }
    }

    /**
     * @param WC_Cart $cart
     */
    private function addFees(WC_Cart $cart): void
    {
        $tax = $this->get_shipping_tax_class();

        foreach ($this->fees as $name) {
            [$string, $fee] = $this->getFee($name);

            if ($string) {
                $cart->add_fee($string, $fee, (bool) $tax, $tax ?? "");
            }
        }
    }

    /**
     * Add a fee to the fees array.
     *
     * @param string $fee
     */
    private function addFee(string $fee): void
    {
        $this->fees[] = $fee;
    }

    /**
     * Map items to prices for display in the checkout.
     *
     * @return array
     */
    private function getFeeMap(): array
    {
        $carrier = $this->deliveryOptions->getCarrier();

        $getCarrierFee = function(string $setting) use ($carrier): float {
            return WCMYPABE()->setting_collection->getFloatByName("{$carrier}_{$setting}");
        };

        return [
            "delivery_evening" => $getCarrierFee(WCMPBE_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE),
            "delivery_morning" => $getCarrierFee(WCMPBE_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE),
            "delivery_pickup"  => $getCarrierFee(WCMPBE_Settings::SETTING_CARRIER_PICKUP_FEE),
            "only_recipient"   => $getCarrierFee(WCMPBE_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE),
            "signature"        => $getCarrierFee(WCMPBE_Settings::SETTING_CARRIER_SIGNATURE_FEE),
        ];
    }

    /**
     * Map items to strings for display in the checkout.
     *
     * @return array
     */
    private function getFeeTitles(): array
    {
        return [
            "delivery_evening" => WCMPBE_Checkout::getDeliveryOptionsTitle(WCMPBE_Settings::SETTING_EVENING_DELIVERY_TITLE),
            "delivery_morning" => WCMPBE_Checkout::getDeliveryOptionsTitle(WCMPBE_Settings::SETTING_MORNING_DELIVERY_TITLE),
            "delivery_pickup"  => WCMPBE_Checkout::getDeliveryOptionsTitle(WCMPBE_Settings::SETTING_PICKUP_TITLE),
            "only_recipient"   => WCMPBE_Checkout::getDeliveryOptionsTitle(WCMPBE_Settings::SETTING_ONLY_RECIPIENT_TITLE),
            "signature"        => WCMPBE_Checkout::getDeliveryOptionsTitle(WCMPBE_Settings::SETTING_SIGNATURE_TITLE),
        ];
    }

    /**
     * Add a delivery fee
     */
    private function addDeliveryFee(): void
    {
        $this->addFee("delivery_{$this->deliveryOptions->getDeliveryType()}");
    }
}
