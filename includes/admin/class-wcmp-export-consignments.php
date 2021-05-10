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
     * WCMP_Export_Consignments constructor.
     *
     * @param WC_Order $order
     *
     * @throws \ErrorException
     * @throws \JsonException
     * @throws \Exception
     */
    public function __construct(WC_Order $order)
    {
        $this->getApiKey();

        $this->order           = $order;
        $this->orderSettings   = new OrderSettings($order);
        $this->deliveryOptions = $this->orderSettings->getDeliveryOptions();

        $this->carrier = $this->deliveryOptions->getCarrier() ?? WCMP_Data::DEFAULT_CARRIER;

        $this->myParcelCollection = (new MyParcelCollection())->setUserAgents(
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
    private function getPackageType(): int
    {
        return WCMP_Data::getPackageTypeId($this->orderSettings->getPackageType());
    }

    /**
     * @return int
     */
    private function getDeliveryType(): int
    {
        $deliveryTypeId = WCMP_Data::getDeliveryTypeId($this->deliveryOptions->getDeliveryType());

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

            // GitHub issue https://github.com/myparcelnl/woocommerce/issues/190
            if (strlen($description) >= WCMP_Export::ITEM_DESCRIPTION_MAX_LENGTH) {
                $description = substr($description, 0, 47) . "...";
            }

            // Amount
            $amount = (int) ($item["qty"] ?? self::DEFAULT_PRODUCT_QUANTITY);

            // Weight (total item weight in grams)
            $weight = WCMP_Export::convertWeightToGrams($product->get_weight());

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
                $emptyParcelWeight = (float) $this->getSetting(WCMYPA_Settings::SETTING_EMPTY_PARCEL_WEIGHT);
                $weight += $emptyParcelWeight;
                break;
            case AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME:
                $digitalStampRangeWeight = $this->orderSettings->getDigitalStampRangeWeight();
                break;
        }

        return $digitalStampRangeWeight ?? WCMP_Export::convertWeightToGrams($weight);
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

        $hsCode = $productHsCode ?: $defaultHsCode;

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
     * @throws \JsonException
     */
    public function getCountryOfOrigin(WC_Product $product): string
    {
        $defaultCountryOfOrigin = $this->getSetting(WCMYPA_Settings::SETTING_COUNTRY_OF_ORIGIN);
        $productCountryOfOrigin = WCX_Product::get_meta($product, WCMYPA_Admin::META_COUNTRY_OF_ORIGIN, true);

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
        $recipient = WCMP_Export::getRecipientFromOrder($this->order);

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
        $this->apiKey = $this->getSetting(WCMYPA_Settings::SETTING_API_KEY);

        if (! $this->apiKey) {
            throw new ErrorException(__("No API key found in MyParcel settings", "woocommerce-myparcel"));
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

        if (strlen($formattedLabelDescription) > WCMP_Export::ORDER_DESCRIPTION_MAX_LENGTH) {
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
            ->setAgeCheck($this->orderSettings->hasAgeCheck())
            ->setInsurance($this->orderSettings->getInsuranceAmount())
            ->setLargeFormat($this->orderSettings->hasLargeFormat())
            ->setOnlyRecipient($this->orderSettings->hasOnlyRecipient())
            ->setReturn($this->orderSettings->hasReturnShipment())
            ->setSignature($this->orderSettings->hasSignature())
            ->setContents($this->getContents())
            ->setInvoice($this->order->get_id());
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

    /**
     * @throws Exception
     */
    public function validate(): bool
    {
        $this->validateWeight();

        return $this->consignment->validate();
    }

    /**
     * @throws \Exception
     */
    public function validateWeight(): void
    {
        $colloWeight       = $this->getTotalWeight();
        $maxForPackageType = WCMP_Data::MAX_COLLO_WEIGHT_PER_PACKAGE_TYPE[$this->getPackageType()];
        $maxColloWeight    = $maxForPackageType ?? WCMP_Data::MAX_COLLO_WEIGHT_PER_PACKAGE_DEFAULT;

        if ($colloWeight > $maxColloWeight) {
            $message = sprintf(
                __('error_collo_weight_%1$s_but_max_%2$s', 'woocommerce-myparcel'),
                $colloWeight / 1000,
                $maxColloWeight / 1000
            );
            $hint    = __('export_hint_change_parcel', 'woocommerce-myparcel');
            throw new Exception("{$message} {$hint}");
        }
    }
}
