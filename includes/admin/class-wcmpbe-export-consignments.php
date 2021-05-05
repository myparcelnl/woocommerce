<?php

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter as DeliveryOptions;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\MyParcelCustomsItem;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Compatibility\Product as WCX_Product;

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMPBE_Export_Consignments")) {
    return;
}

class WCMPBE_Export_Consignments
{
    private const DEFAULT_PRODUCT_QUANTITY = 1;

    /**
     * @var AbstractConsignment
     */
    private $consignment;

    /**
     * @var DeliveryOptions
     */
    private $deliveryOptions;

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
     * @var \OrderSettings
     */
    private $orderSettings;

    /**
     * WCMPBE_Export_Consignments constructor.
     *
     * @param WC_Order $order
     *
     * @throws \ErrorException
     * @throws \JsonException
     * @throws \Exception
     */
    public function __construct(WC_Order $order)
    {
        $defaultCarrier = $this->getSetting(WCMPBE_Settings::SETTING_DEFAULT_CARRIER) ?? WCMPBE_Data::DEFAULT_CARRIER;
        $this->getApiKey();

        $this->order           = $order;
        $this->orderSettings   = new OrderSettings($order);
        $this->deliveryOptions = $this->orderSettings->getDeliveryOptions();
        $this->carrier         = $this->deliveryOptions->getCarrier() ?? $defaultCarrier;

        $this->myParcelCollection = (new MyParcelCollection())->setUserAgents(
            [
                'Wordpress'              => get_bloginfo('version'),
                'WooCommerce'            => WOOCOMMERCE_VERSION,
                'MyParcelBE-WooCommerce' => WC_MYPARCEL_BE_VERSION,
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
        return WCMYPABE()->setting_collection->getByName($name);
    }

    /**
     * @return int
     */
    private function getPackageType(): int
    {
        return WCMPBE_Data::getPackageTypeId($this->orderSettings->getPackageType());
    }

    /**
     * @return int
     */
    private function getDeliveryType(): int
    {
        $deliveryTypeId = WCMPBE_Data::getDeliveryTypeId($this->deliveryOptions->getDeliveryType());

        return $deliveryTypeId ?? AbstractConsignment::DELIVERY_TYPE_STANDARD;
    }

    /**
     * Get date in YYYY-MM-DD HH:MM:SS format
     *
     * @return string
     */
    public function getDeliveryDate(): string
    {
        $date             = strtotime($this->deliveryOptions->getDate());
        $deliveryDateTime = date('Y-m-d H:i:s', $date);
        $deliveryDate     = date("Y-m-d", $date);
        $dateOfToday      = date("Y-m-d", strtotime('now'));
        $dateOfTomorrow   = date('Y-m-d H:i:s', strtotime('now +1 day'));

        if ($deliveryDate <= $dateOfToday) {
            return $dateOfTomorrow;
        }

        return $deliveryDateTime;
    }

    /**
     * @return void
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     * @throws \ErrorException
     */
    public function setCustomItems(): void
    {
        foreach ($this->order->get_items() as $item) {
            $product = $item->get_product();

            if (! $product) {
                return;
            }

            $country     = $this->getCountryOfOrigin($product);
            $description = $item["name"];

            // GitHub issue https://github.com/myparcelbe/woocommerce/issues/190
            if (strlen($description) >= WCMPBE_Export::ITEM_DESCRIPTION_MAX_LENGTH) {
                $description = substr($description, 0, 47) . "...";
            }

            // Amount
            $amount = (int) ($item["qty"] ?? self::DEFAULT_PRODUCT_QUANTITY);

            // Weight (total item weight in grams)
            $weight = WCMPBE_Export::convertWeightToGrams($product->get_weight());

            $total = (int) $item["line_total"];
            $tax   = (int) $item["line_tax"];
            $value = round(($total + $tax) * 100);

            $this->consignment->addItem(
                (new MyParcelCustomsItem())->setDescription($description)
                    ->setAmount($amount)
                    ->setWeight($weight)
                    ->setItemValue($value)
                    ->setCountry($country)
                    ->setClassification($this->getHsCode($product))
            );
        }
    }

    /**
     * Returns the weight of the order plus the empty parcel weight.
     *
     * @return int
     */
    private function getTotalWeight(): int
    {
        $digitalStampRangeWeight = null;
        $weight                  = $this->orderSettings->getWeight();

        // Divide the consignment weight by the amount of parcels.
        $weight /= $this->orderSettings->getColloAmount();

        switch ($this->orderSettings->getPackageType()) {
            case AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME:
                $emptyParcelWeight = (float) $this->getSetting(WCMPBE_Settings::SETTING_EMPTY_PARCEL_WEIGHT);
                $weight += $emptyParcelWeight;
                break;
            case AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME:
                $digitalStampRangeWeight = $this->orderSettings->getDigitalStampRangeWeight();
                break;
        }

        return $digitalStampRangeWeight ?? WCMPBE_Export::convertWeightToGrams($weight);
    }

    /**
     * @param WC_Product $product
     *
     * @return int
     * @throws \ErrorException
     */
    public function getHsCode(WC_Product $product): int
    {
        $defaultHsCode   = $this->getSetting(WCMPBE_Settings::SETTING_HS_CODE);
        $productHsCode   = WCX_Product::get_meta($product, WCMYPABE_Admin::META_HS_CODE, true);
        $variationHsCode = WCX_Product::get_meta($product, WCMYPABE_Admin::META_HS_CODE_VARIATION, true);

        $hsCode = $productHsCode ?: $defaultHsCode;

        if ($variationHsCode) {
            $hsCode = $variationHsCode;
        }

        if (! $hsCode) {
            throw new ErrorException(__("No HS code found in MyParcel BE settings", "woocommerce-myparcelbe"));
        }

        return (int) $hsCode;
    }

    /**
     * @param WC_Product $product
     *
     * @return string
     * @throws \JsonException
     */
    public function getCountryOfOrigin(WC_Product $product): string
    {
        $defaultCountryOfOrigin = $this->getSetting(WCMPBE_Settings::SETTING_COUNTRY_OF_ORIGIN);
        $productCountryOfOrigin = WCX_Product::get_meta($product, WCMYPABE_Admin::META_COUNTRY_OF_ORIGIN, true);

        return $this->getPriorityOrigin($defaultCountryOfOrigin, $productCountryOfOrigin);
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
     * @return \OrderSettings
     */
    public function getOrderSettings(): OrderSettings
    {
        return $this->orderSettings;
    }

    /**
     * @return int
     */
    private function getContents(): int
    {
        return (int) ($this->getSetting("package_contents") ?? AbstractConsignment::PACKAGE_CONTENTS_COMMERCIAL_GOODS);
    }

    /**
     * Gets the recipient and puts its data in the consignment.
     *
     * @throws Exception
     */
    private function setRecipient(): void
    {
        $recipient = WCMPBE_Export::getRecipientFromOrder($this->order);

        $this->consignment
            ->setCountry($recipient['cc'])
            ->setPerson($recipient['person'])
            ->setCompany($recipient['company'])
            ->setStreet($recipient['street'])
            ->setNumber($recipient['number'] ?? null)
            ->setNumberSuffix($recipient['number_suffix'] ?? null)
            ->setStreetAdditionalInfo($recipient['street_additional_info'] ?? null)
            ->setPostalCode($recipient['postal_code'])
            ->setCity($recipient['city'])
            ->setEmail($recipient['email'])
            ->setPhone($recipient['phone'])
            ->setSaveRecipientAddress(false);
    }

    /**
     * @throws ErrorException
     */
    private function getApiKey(): void
    {
        $this->apiKey = $this->getSetting(WCMPBE_Settings::SETTING_API_KEY);

        if (! $this->apiKey) {
            throw new ErrorException(__("No API key found in MyParcel BE settings", "woocommerce-myparcelbe"));
        }
    }

    /**
     * Get the label description from OrderSettings and replace any variables in it.
     *
     * @return string
     */
    private function getFormattedLabelDescription(): string
    {
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

        $formattedLabelDescription = strtr(
            $this->orderSettings->getLabelDescription(),
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

        if (strlen($formattedLabelDescription) > WCMPBE_Export::ORDER_DESCRIPTION_MAX_LENGTH) {
            return substr($formattedLabelDescription, 0, 42) . "...";
        }

        return $formattedLabelDescription;
    }

    /**
     * Set the pickup location
     */
    private function setPickupLocation(): void
    {
        $pickupLocation = $this->deliveryOptions->getPickupLocation();

        if (! $this->deliveryOptions->isPickup() || ! $pickupLocation) {
            return;
        }

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
            ->setInsurance($this->getInsurance())
            ->setLargeFormat($this->orderSettings->hasLargeFormat())
            ->setOnlyRecipient($this->orderSettings->hasOnlyRecipient())
            ->setSignature($this->orderSettings->hasSignature())
            ->setContents($this->getContents())
            ->setInvoice($this->order->get_id());
    }

    /**
     * Get the value of the insurance setting. Changes true/false to either 500 or 0 because the API expects an amount.
     *
     * @return int
     */
    private function getInsurance(): int
    {
        $isInsuranceActive = WCMPBE_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->getInsurance(),
            "{$this->carrier}_" . WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED
        );

        return $this->getInsuranceAmount($isInsuranceActive);
    }

    private function getInsuranceAmount($isInsuranceActive): int
    {
        // Checks if all parcels must be insured
        if ($isInsuranceActive) {
            // get min price for insurance
            $insuranceFromPrice = (float)$this->getSetting("{$this->carrier}_" .
                WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FROM_PRICE
            );

            $insuranceMaxPrice = 500;

            if ($this->carrier === 'dpd') {
                $insuranceMaxPrice === 520;
            }

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
     * Sets a customs declaration for the consignment if necessary.
     *
     * @throws \Exception
     */
    private function setCustomsDeclaration(): void
    {
        $shippingCountry = WCX_Order::get_prop($this->order, "shipping_country");

        if (WCMPBE_Country_Codes::isWorldShipmentCountry($shippingCountry)) {
            $this->setCustomItems();
        }
    }

    /**
     * @throws \Exception
     */
    private function setPhysicalProperties(): void
    {
        $this->consignment->setPhysicalProperties(
            [
                'weight' => $this->getTotalWeight(),
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
            ->setLabelDescription($this->getFormattedLabelDescription());
    }
}
