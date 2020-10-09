<?php

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter as DeliveryOptions;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\MyParcelCustomsItem;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMP_Export_Consignments")) {
    return;
}

class WCMP_Export_Consignments
{
    /**
     * @var AbstractConsignment
     */
    private $consignment;

    /**
     * @var DeliveryOptions
     */
    private $deliveryOptions;

    /**
     * @var mixed
     */
    private $recipient;

    /**
     * @var WC_Order
     */
    private $order;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string|null
     */
    private $carrier;

    /**
     * @var MyParcelCollection
     */
    public $myParcelCollection;

    /**
     * WCMP_Export_Consignments constructor.
     *
     * @param WC_Order $order
     *
     * @throws ErrorException
     * @throws Exception
     */
    public function __construct(WC_Order $order)
    {
        $this->getApiKey();

        $this->order           = $order;
        $this->deliveryOptions = WCMYPA_Admin::getDeliveryOptionsFromOrder($order);
        $this->carrier         = $this->deliveryOptions->getCarrier() ?? WCMP_Data::DEFAULT_CARRIER;

        $this->myParcelCollection = (new MyParcelCollection())->setUserAgentArray(
            [
                'Wordpress'              => get_bloginfo('version'),
                'WooCommerce'            => WOOCOMMERCE_VERSION,
                'MyParcelNL-WooCommerce' => WC_MYPARCEL_NL_VERSION,
            ]
        );

        $this->createConsignment();
        $this->setConsignmentData();
    }

    /**
     * Create a new consignment
     *
     * @return void
     * @throws Exception
     */
    public function createConsignment(): void
    {
        $this->consignment = ConsignmentFactory::createByCarrierName($this->carrier);
    }

    /**
     * Set all the needed data for the consignment.
     *
     * @throws Exception
     */
    private function setConsignmentData(): void
    {
        $this->setBaseData();
        $this->setRecipient();
        $this->setShipmentOptions();
        $this->setPickupLocation();
        $this->setCustomsDeclaration();
        $this->setPhysicalProperties();
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    private function getSetting(string $name)
    {
        return WCMYPA()->setting_collection->getByName($name);
    }

    /**
     * @return int
     */
    private function getDeliveryType(): int
    {
        return AbstractConsignment::DELIVERY_TYPES_NAMES_IDS_MAP[$this->deliveryOptions->getDeliveryType() ?? AbstractConsignment::DELIVERY_TYPE_STANDARD_NAME];
    }

    /**
     * Get date in YYYY-MM-DD HH:MM:SS format
     *
     * @return string
     */
    public function getDeliveryDate(): string
    {
        $date         = strtotime($this->deliveryOptions->getDate());
        $deliveryDate = date('Y-m-d H:i:s', $date);
        $todayDate    = strtotime('now');

        if ($date <= $todayDate) {
            return date('Y-m-d H:i:s', strtotime('now +1 day'));
        }

        return $deliveryDate;
    }

    /**
     * @return void
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     * @throws \ErrorException
     */
    public function setCustomItems(): void
    {
        foreach ($this->order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $country = $this->getCountryOfOrigin($product);
            if (! empty($product)) {
                // Description
                $description = $item["name"];

                // GitHub issue https://github.com/myparcelnl/woocommerce/issues/190
                if (strlen($description) >= WCMP_Export::DESCRIPTION_MAX_LENGTH) {
                    $description = substr($item["name"], 0, 47) . "...";
                }
                // Amount
                $amount = (int) (isset($item["qty"]) ? $item["qty"] : 1);

                // Weight (total item weight in grams)
                $weight      = (int) round(WCMP_Export::getItemWeight_kg($item, $this->order) * 1000);
                $totalWeight = $this->getTotalWeight($weight);

                $myParcelItem = (new MyParcelCustomsItem())
                    ->setDescription($description)
                    ->setAmount($amount)
                    ->setWeight($totalWeight)
                    ->setItemValue((int) round(($item["line_total"] + $item["line_tax"]) * 100))
                    ->setCountry($country)
                    ->setClassification($this->getHsCode($product));

                $this->consignment->addItem($myParcelItem);
            }
        }
    }

    /**
     * @param int $weight
     *
     * @return int
     */
    private function getTotalWeight(int $weight): int
    {
        $parcelWeight = (int) $this->getSetting(WCMYPA_Settings::SETTING_EMPTY_PARCEL_WEIGHT);

        return $parcelWeight + $weight;
    }

    /**
     * @param WC_Product $product
     *
     * @return int
     * @throws \ErrorException
     */
    public function getHsCode(WC_Product $product): int
    {
        $defaultHsCode   = $this->getSetting(WCMYPA_Settings::SETTING_HS_CODE);
        $productHsCode   = WCX_Product::get_meta($product, WCMYPA_Admin::META_HS_CODE, true);
        $variationHsCode = WCX_Product::get_meta($product, WCMYPA_Admin::META_HS_CODE_VARIATION, true);

        $hsCode = $productHsCode ? $productHsCode : $defaultHsCode;

        if ($variationHsCode) {
            $hsCode = $variationHsCode;
        }

        if (! $hsCode) {
            throw new ErrorException(__("No HS code found in MyParcel settings", "woocommerce-myparcel"));
        }

        return (int) $hsCode;
    }

    /**
     * @param WC_Product $product
     *
     * @return string
     */
    public function getCountryOfOrigin(WC_Product $product): string
    {
        $defaultCountryOfOrigin = $this->getSetting(WCMYPA_Settings::SETTING_COUNTRY_OF_ORIGIN);
        $productCountryOfOrigin = WCX_Product::get_meta($product, WCMYPA_Admin::META_COUNTRY_OF_ORIGIN, true);

        $countryOfOrigin = $this->getPriorityOrigin($defaultCountryOfOrigin, $productCountryOfOrigin);

        return (string) $countryOfOrigin;
    }

    /**
     * @param string|null $defaultCountryOfOrigin
     * @param string|null  $productCountryOfOrigin
     *
     * @return string
     */
    public function getPriorityOrigin(?string $defaultCountryOfOrigin, ?string $productCountryOfOrigin): string
    {
        if ($productCountryOfOrigin) {
            return $productCountryOfOrigin;
        }

        if ($defaultCountryOfOrigin) {
            return $defaultCountryOfOrigin;
        }

        return WC()->countries->get_base_country() ?? AbstractConsignment::CC_NL;
    }

    /**
     * @return AbstractConsignment
     */
    public function getConsignment(): AbstractConsignment
    {
        return $this->consignment;
    }

    /**
     * @return int
     * @throws Exception
     */
    private function getPackageType(): int
    {
        return WCMYPA()->export->getPackageTypeFromOrder($this->order, $this->deliveryOptions);
    }

    /**
     * @return bool
     */
    private function getSignature(): bool
    {
        return (bool) WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->hasSignature(),
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE
        );
    }

    /**
     * @return bool
     */
    private function getOnlyRecipient(): bool
    {
        return (bool) WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->hasOnlyRecipient(),
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT
        );
    }

    /**
     * @return bool
     */
    private function getAgeCheck(): bool
    {
        $ageCheckOfProduct = $this->getAgeCheckOfProduct();
        $defaultAgeCheck   = (bool) WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->hasAgeCheck(),
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK
        );

        return $ageCheckOfProduct ?: $defaultAgeCheck;
    }

    /**
     * @return bool
     */
    private function getAgeCheckOfProduct(): bool
    {
        foreach ($this->order->get_items() as $item) {
            $product         = $item->get_product();
            $productAgeCheck = WCX_Product::get_meta($product, WCMYPA_Admin::META_AGE_CHECK, true);

            if ($productAgeCheck) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function getLargeFormat(): bool
    {
        return (bool) WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->hasLargeFormat(),
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT
        );
    }

    /**
     * @return int
     */
    private function getContents(): int
    {
        return (int) ($this->getSetting("package_contents") ?? AbstractConsignment::PACKAGE_CONTENTS_COMMERCIAL_GOODS);
    }

    /**
     * @return bool
     */
    private function getReturnShipment(): bool
    {
        return (bool) WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->isReturn(),
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN
        );
    }

    /**
     * Get the value of the insurance setting. Changes true/false to either 500 or 0 because the API expects an amount.
     *
     * @return int
     */
    private function getInsurance(): int
    {
        $deliveryOptionsInsurance = $this->deliveryOptions->getShipmentOptions()->getInsurance();

        if ($deliveryOptionsInsurance) {
            return $deliveryOptionsInsurance;
        }

        $insured = WCMP_Export::getChosenOrDefaultShipmentOption(
            null,
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED
        );

        return $this->getInsuranceAmount((bool) $insured);
    }

    /**
     * @param bool $isInsuranceActive
     *
     * @return int
     */
    private function getInsuranceAmount(bool $isInsuranceActive): int
    {
        // Checks if all parcels must be insured
        if ($isInsuranceActive) {
            // get min price for insurance
            $insuranceFromPrice = (float) $this->getSetting("{$this->carrier}_" .
                WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FROM_PRICE
            );

            $insuranceMaxPrice = (int) $this->getSetting("{$this->carrier}_" .
                WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT);

            // get the order's total price
            $orderPrice = (float) $this->order->get_total();

            if ($insuranceFromPrice <= $orderPrice) {
                // returns max allowed insured amount.
                return $insuranceMaxPrice;
            }
        }

        return 0;
    }

    /**
     * Gets the recipient and puts its data in the consignment.
     *
     * @throws Exception
     */
    private function setRecipient(): void
    {
        $this->recipient = WCMP_Export::getRecipientFromOrder($this->order);

        $this->consignment
            ->setCountry($this->recipient['cc'])
            ->setPerson($this->recipient['person'])
            ->setCompany($this->recipient['company'])
            ->setStreet($this->recipient['street'])
            ->setNumber($this->recipient['number'] ?? null)
            ->setNumberSuffix($this->recipient['number_suffix'] ?? null)
            ->setStreetAdditionalInfo($this->recipient['street_additional_info'] ?? null)
            ->setPostalCode($this->recipient['postal_code'])
            ->setCity($this->recipient['city'])
            ->setEmail($this->recipient['email'])
            ->setPhone($this->recipient['phone']);
    }

    /**
     * @throws ErrorException
     */
    private function getApiKey(): void
    {
        $this->apiKey = $this->getSetting(WCMYPA_Settings::SETTING_API_KEY);

        if (! $this->apiKey) {
            throw new ErrorException(__("No API key found in MyParcel settings", "woocommerce-myparcel"));
        }
    }

    /**
     * @return string
     */
    private function getLabelDescription(): string
    {
        $default = "Order: " . $this->order->get_id();
        $setting = $this->getSetting(WCMYPA_Settings::SETTING_LABEL_DESCRIPTION);

        if ($setting) {
            $productIds   = [];
            $productNames = [];
            $productSkus  = [];

            foreach ($this->order->get_items() as $item) {
                if (! method_exists($item, 'get_product')) {
                    continue;
                }

                /** @var WC_Product $product */
                $product = $item->get_product();
                $sku     = $product->get_sku();

                $productIds[]   = $product->get_id();
                $productNames[] = $product->get_name();
                $productSkus[]  = empty($sku) ? '–' : $sku;
            }

            $description = strtr(
                $setting,
                [
                    '[DELIVERY_DATE]' => date('d-m-Y', strtotime($this->deliveryOptions->getDate())),
                    '[ORDER_NR]'      => $this->order->get_order_number(),
                    '[PRODUCT_ID]'    => implode(', ', $productIds),
                    '[PRODUCT_NAME]'  => implode(', ', $productNames),
                    '[PRODUCT_QTY]'   => count($this->order->get_items()),
                    '[PRODUCT_SKU]'   => implode(', ', $productSkus),
                    '[CUSTOMER_NOTE]' => $this->order->get_customer_note(),
                ]
            );
        }

        return ! empty($description) ? $description : $default;
    }

    /**
     * Set the pickup location
     */
    private function setPickupLocation(): void
    {
        if (! $this->deliveryOptions->isPickup()) {
            return;
        }

        $pickupLocation = $this->deliveryOptions->getPickupLocation();

        $this->consignment
            ->setPickupCountry($pickupLocation->getCountry())
            ->setPickupCity($pickupLocation->getCity())
            ->setPickupLocationName($pickupLocation->getLocationName())
            ->setPickupStreet($pickupLocation->getStreet())
            ->setPickupNumber($pickupLocation->getNumber())
            ->setPickupPostalCode($pickupLocation->getPostalCode())
            ->setRetailNetworkId($pickupLocation->getRetailNetworkId())
            ->setPickupLocationCode($pickupLocation->getLocationCode());
    }

    /**
     * Set the shipment options.
     *
     * @throws Exception
     */
    private function setShipmentOptions(): void
    {
        $this->consignment
            ->setSignature($this->getSignature())
            ->setOnlyRecipient($this->getOnlyRecipient())
            ->setInsurance($this->getInsurance())
            ->setAgeCheck($this->getAgeCheck())
            ->setLargeFormat($this->getLargeFormat())
            ->setContents($this->getContents())
            ->setInvoice($this->order->get_id())
            ->setReturn($this->getReturnShipment());
    }

    /**
     * Sets a customs declaration for the consignment if necessary.
     *
     * @throws \Exception
     */
    private function setCustomsDeclaration(): void
    {
        $shippingCountry = WCX_Order::get_prop($this->order, "shipping_country");

        if (WCMP_Country_Codes::isWorldShipmentCountry($shippingCountry)) {
            $this->setCustomItems();
        }
    }

    /**
     * Sets a customs declaration for the consignment if necessary.
     *
     * @throws \Exception
     */
    private function setPhysicalProperties(): void
    {
        $extraOptions = WCX_Order::get_meta($this->order, WCMYPA_Admin::META_SHIPMENT_OPTIONS_EXTRA);

        if (! $extraOptions) {
            return;
        }

        $digitalStampWeight = $extraOptions['weight'];
        $orderWeight        = $this->order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT);

        $this->consignment->setPhysicalProperties(
            [
                "weight" => $this->getTotalWeight((int) ($digitalStampWeight ?? $orderWeight)),
            ]
        );
    }

    /**
     * @throws Exception
     */
    private function setBaseData(): void
    {
        $this->consignment
            ->setApiKey($this->apiKey)
            ->setReferenceId((string) $this->order->get_id())
            ->setPackageType($this->getPackageType())
            ->setDeliveryDate($this->getDeliveryDate())
            ->setDeliveryType($this->getDeliveryType())
            ->setLabelDescription($this->getLabelDescription());
    }
}
