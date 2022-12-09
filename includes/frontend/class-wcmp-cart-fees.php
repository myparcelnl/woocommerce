<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter as DeliveryOptions;
use MyParcelNL\Sdk\src\Factory\DeliveryOptionsAdapterFactory;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Cart_Fees')) {
    return;
}

/**
 * Frontend views
 */
class WCMP_Cart_Fees
{
    // We treat same day here like a delivery type, even though it is a shipment option.
    private const SAME_DAY = 'same_day';

    /**
     * @var array
     */
    private $feeMap;

    /**
     * @var array
     */
    private $feeTitles;

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

        $post = wp_unslash(filter_input_array(INPUT_POST));

        if (isset($post['post_data'])) {
            // non-default post data for AJAX calls
            parse_str($post['post_data'], $postData);
        } else {
            // checkout finalization
            $postData = $post;
        }

        // Check if delivery options exist at all.
        if (empty($postData[WCMYPA_Admin::META_DELIVERY_OPTIONS])) {
            return;
        }

        $deliveryOptionsData = $postData[WCMYPA_Admin::META_DELIVERY_OPTIONS];
        $deliveryOptionsData = json_decode(stripslashes($deliveryOptionsData), true);

        /*
         * Check if delivery options is null. Happens when switching to a shipping method that does not allow
         * showing delivery options, for example.
         */
        if (!$deliveryOptionsData) {
            return;
        }

        $this->deliveryOptions = DeliveryOptionsAdapterFactory::create($deliveryOptionsData);
        $this->feeMap    = $this->getFeeMap();
        $this->feeTitles = $this->getFeeTitles();

        $this->addDeliveryFee();
        $this->addShipmentOptionFees();

        $this->addFees($cart);
    }

    /**
     * @param  string $name
     *
     * @return array
     */
    private function getFee(string $name): array
    {
        return [
            $this->feeTitles[$name] ?? null,
            $this->feeMap[$name] ?? 0,
        ];
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
            [, $state, $postcode, $city] = $location;

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
            if (AbstractConsignment::SHIPMENT_OPTION_SAME_DAY_DELIVERY === $shipmentOption) {
                return;
            }

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

            if ($string && $fee) {
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
        $carrier       = $this->deliveryOptions->getCarrier();
        $getCarrierFee = static function (string $setting) use ($carrier): float {
            return (float) WCMYPA()->setting_collection->where('carrier', $carrier)->getByName($setting);
        };

        return [
            'delivery_evening'  => $getCarrierFee(WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE),
            'delivery_standard' => $getCarrierFee(WCMYPA_Settings::SETTING_CARRIER_DELIVERY_STANDARD_FEE),
            'delivery_same_day' => $getCarrierFee(WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY_FEE),
            'delivery_morning'  => $getCarrierFee(WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE),
            'delivery_pickup'   => $getCarrierFee(WCMYPA_Settings::SETTING_CARRIER_PICKUP_FEE),
            'only_recipient'    => $getCarrierFee(WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE),
            'signature'         => $getCarrierFee(WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_FEE),
        ];
    }

    /**
     * Map items to strings for display in the checkout. Add fallback values for titles because it's possible to not
     * have titles, if there is no fallback string the fee won't be added to the checkout.
     *
     * @return array
     */
    private function getFeeTitles(): array
    {

        return [
            'delivery_evening'  => WCMP_Checkout::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_EVENING_DELIVERY_TITLE) ?: __('shipment_options_delivery_evening', 'woocommerce-myparcel'),
            'delivery_standard' => WCMP_Checkout::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_STANDARD_TITLE) ?: __('shipment_options_delivery_standard', 'woocommerce-myparcel'),
            'delivery_same_day' => WCMP_Checkout::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_SAME_DAY_TITLE) ?: __('shipment_options_delivery_same_day', 'woocommerce-myparcel'),
            'delivery_morning'  => WCMP_Checkout::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_MORNING_DELIVERY_TITLE) ?: __('shipment_options_delivery_morning', 'woocommerce-myparcel'),
            'delivery_pickup'   => WCMP_Checkout::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_PICKUP_TITLE) ?: __('shipment_options_delivery_pickup', 'woocommerce-myparcel'),
            'only_recipient'    => WCMP_Checkout::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_ONLY_RECIPIENT_TITLE) ?: __('shipment_options_only_recipient', 'woocommerce-myparcel'),
            'signature'         => WCMP_Checkout::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_SIGNATURE_TITLE) ?: __('shipment_options_signature', 'woocommerce-myparcel'),
        ];
    }

    /**
     * Add a delivery fee
     */
    private function addDeliveryFee(): void
    {
        $deliveryType = $this->deliveryOptions->getDeliveryType();

        if ($this->deliveryOptions->getShipmentOptions()
            && $this->deliveryOptions->getShipmentOptions()->isSameDayDelivery()) {
            $deliveryType = self::SAME_DAY;
        }

        $this->addFee("delivery_{$deliveryType}");
    }
}
