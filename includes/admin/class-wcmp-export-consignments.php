<?php

use MyParcelNL\Sdk\src\Exception\MissingFieldException;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter as DeliveryOptions;
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

    private $referenceId;

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
        $this->deliveryOptions = WCMP_Admin::getDeliveryOptionsFromOrder($order);
        $this->carrier         = $this->deliveryOptions->getCarrier() ?? WCMP_Data::DEFAULT_CARRIER;

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
        return WCMP()->setting_collection->getByName($name);
    }

    /**
     * @param DeliveryOptions $delivery_options
     *
     * @return int
     */
    private function getPickupTypeByDeliveryOptions(DeliveryOptions $delivery_options): int
    {
        return AbstractConsignment::DELIVERY_TYPES_NAMES_IDS_MAP[$delivery_options->getDeliveryType() ?? AbstractConsignment::DELIVERY_TYPE_STANDARD_NAME];
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
                $weight       = (int) round(WCMP_Export::getItemWeight_kg($item, $this->order) * 1000);
                $myParcelItem = (new MyParcelCustomsItem())
                    ->setDescription($description)
                    ->setAmount($amount)
                    ->setWeight($weight)
                    ->setItemValue((int) round(($item["line_total"] + $item["line_tax"]) * 100))
                    ->setCountry($country)
                    ->setClassification($this->getHsCode($product));

                $this->consignment->addItem($myParcelItem);
            }
        }
    }

    /**
     * @param WC_Product $product
     *
     * @return int
     * @throws \ErrorException
     */
    public function getHsCode(WC_Product $product): int
    {
        $defaultHsCode = $this->getSetting(WCMP_Settings::SETTING_HS_CODE);
        $productHsCode = WCX_Product::get_meta($product, WCMP_Admin::META_HS_CODE, true);

        $hsCode = $productHsCode ? $productHsCode : $defaultHsCode;

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
        $defaultCountryOfOrigin = $this->getSetting(WCMP_Settings::SETTING_COUNTRY_OF_ORIGIN);
        $productCountryOfOrigin = WCX_Product::get_meta($product, WCMP_Admin::META_COUNTRY_OF_ORIGIN, true);

        $countryOfOrigin = getPriorityOrigin($defaultCountryOfOrigin, $productCountryOfOrigin);

        return (string) $countryOfOrigin;
    }

    /**
     * @param $defaultCountryOfOrigin
     * @param $productCountryOfOrigin
     *
     * @return string
     */
    public function getPriorityOrigin($defaultCountryOfOrigin, $productCountryOfOrigin): string
    {
        if ($defaultCountryOfOrigin) {
            return $defaultCountryOfOrigin;
        }

        if (! $defaultCountryOfOrigin) {
            if (! $productCountryOfOrigin) {
                return WC()->countries->baseCountry() ?? 'NL';
            }
        }

        return $productCountryOfOrigin;
    }

    /**
     * @return bool
     */
    private function getSignature(): bool
    {
        return WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->hasSignature(),
            "{$this->carrier}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE
        );
    }

    /**
     * @return bool
     */
    private function getOnlyRecipient(): bool
    {
        return WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->hasOnlyRecipient(),
            "{$this->carrier}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT
        );
    }

    /**
     * @return bool
     */
    private function getAgeCheck(): bool
    {
        return WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->hasAgeCheck(),
            "{$this->carrier}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK
        );
    }

    /**
     * @return bool
     */
    private function getLargeFormat(): bool
    {
        return WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->hasLargeFormat(),
            "{$this->carrier}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT
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
        return WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->isReturn(),
            "{$this->carrier}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN
        );
    }

    /**
     * Get the value of the insurance setting. Changes true/false to either 500 or 0 because the API expects an amount.
     *
     * @return int
     */
    private function getInsurance(): int
    {
        $isInsuranceActive = WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->deliveryOptions->getShipmentOptions()->getInsurance(),
            "{$this->carrier}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED
        );

        $insuranceAmount = $this->getInsuranceAmount($isInsuranceActive);

        return $insuranceAmount;
    }

    /**
     * @param $isInsuranceActive
     *
     * @return int|mixed
     */
    private function getInsuranceAmount($isInsuranceActive)
    {
        if ($isInsuranceActive) {
            return $this->getSetting("{$this->carrier}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT);
        }

        return 0;
    }

    /**
     * @return int
     */
    private function getTotalPackageWeight(): int
    {
        return $this->order->get_meta(WCMP_Admin::META_ORDER_WEIGHT);
    }

    /**
     * Gets the recipient and puts its data in the consignment.
     *
     * @throws Exception
     */
    private function setRecipient(): void
    {
        $connectEmail    = $this->carrier === PostNLConsignment::CARRIER_NAME;
        $this->recipient = WCMP_Export::getRecipientFromOrder($this->order, $connectEmail);

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
        $this->apiKey = $this->getSetting(WCMP_Settings::SETTING_API_KEY);

        if (! $this->apiKey) {
            throw new ErrorException(__("No API key found in MyParcel settings", "woocommerce-myparcel"));
        }
    }

    /**
     * @return mixed|string
     */
    private function getLabelDescription()
    {
        $default = "Order: " . $this->order->get_id();
        $setting = $this->getSetting(WCMP_Settings::SETTING_LABEL_DESCRIPTION);

        if ($setting) {
            $replacements = [
                "[ORDER_NR]"      => $this->order->get_order_number(),
                "[DELIVERY_DATE]" => $this->deliveryOptions->getDate(),
            ];

            $description = str_replace(array_keys($replacements), array_values($replacements), $setting);
        }

        return $description ?? $default;
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
            ->setPickupLocationCode($pickupLocation->getLocationCode());
    }

    /**
     * Set the shipment options.
     *
     * @throws Exception
     */
    private function setShipmentOptions()
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
    private function setCustomsDeclaration()
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
    private function setPhysicalProperties()
    {
        $this->consignment
            ->setPhysicalProperties(["weight" => $this->getTotalPackageWeight()]);
    }

    private function setBaseData(): void
    {
        $this->consignment
            ->setApiKey($this->apiKey)
            ->setReferenceId((string) $this->order->get_id())
            ->setDeliveryType($this->getPickupTypeByDeliveryOptions($this->deliveryOptions))
            ->setLabelDescription($this->getLabelDescription())
            ->setPackageType(WCMP()->export->getPackageTypeForOrder($this->order->get_id()));
    }

    /**
     * @return AbstractConsignment
     */
    public function getConsignment(): AbstractConsignment
    {
        return $this->consignment;
    }
}
