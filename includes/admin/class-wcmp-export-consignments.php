<?php

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter as DeliveryOptions;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\MyParcelCustomsItem;
use MyParcelNL\WooCommerce\Helper\ExportRow;
use MyParcelNL\WooCommerce\Helper\LabelDescriptionFormat;
use MyParcelNL\WooCommerce\includes\adapter\RecipientFromWCOrder;
use MyParcelNL\WooCommerce\includes\admin\OrderSettings;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;

defined('ABSPATH') or die();

class WCMP_Export_Consignments
{
    use HasApiKey;

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
     * @var OrderSettings
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

        $this->carrier = $this->deliveryOptions->getCarrier() ?? (WCMP_Data::DEFAULT_CARRIER_CLASS)::NAME;

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
        $this->setDropOffPoint();
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
     * @throws \JsonException
     */
    public function setCustomItems(): void
    {
        foreach ($this->order->get_items() as $item) {
            $product = $item->get_product();

            if (! $product || $product->is_virtual()) {
                continue;
            }

            $productHelper = new ExportRow($this->order, $product);

            $this->consignment->addItem(
                (new MyParcelCustomsItem())
                    ->setDescription($productHelper->getItemDescription())
                    ->setAmount($productHelper->getItemAmount($item))
                    ->setWeight($productHelper->getItemWeight())
                    ->setItemValue($this->getValueOfItem($item))
                    ->setCountry($productHelper->getCountryOfOrigin())
                    ->setClassification($productHelper->getHsCode())
            );
        }
    }

    /**
     * @param WC_Order_Item $item
     *
     * @return int
     */
    private function getValueOfItem(WC_Order_Item $item): int
    {
        $total = (int) $item['line_total'];
        $tax   = (int) $item['line_tax'];

        return ($total + $tax) * 100;
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
                $weight            += $emptyParcelWeight;
                break;
            case AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME:
                $digitalStampRangeWeight = $this->orderSettings->getDigitalStampRangeWeight();
                break;
        }

        return $digitalStampRangeWeight ?? WCMP_Export::convertWeightToGrams($weight);
    }

    /**
     * @return AbstractConsignment
     */
    public function getConsignment(): AbstractConsignment
    {
        return $this->consignment;
    }

    /**
     * @return OrderSettings
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
        $originCountry = $this->consignment->getLocalCountryCode();
        $recipient     = new RecipientFromWCOrder($this->order, $originCountry, RecipientFromWCOrder::SHIPPING);

        $this->consignment
            ->setCountry($recipient->getCc())
            ->setPerson($recipient->getPerson())
            ->setCompany($recipient->getCompany())
            ->setStreetAdditionalInfo($recipient->getStreetAdditionalInfo())
            ->setNumber($recipient->getNumber())
            ->setNumberSuffix($recipient->getNumberSuffix())
            ->setBoxNumber($recipient->getBoxNumber())
            ->setStreet($recipient->getStreet())
            ->setPostalCode($recipient->getPostalCode())
            ->setCity($recipient->getCity())
            ->setRegion($recipient->getRegion())
            ->setEmail($recipient->getEmail())
            ->setPhone($recipient->getPhone())
            ->setSaveRecipientAddress(false);
    }

    /**
     * Get the label description from OrderSettings and replace any variables in it.
     *
     * @return string
     */
    private function getFormattedLabelDescription(): string
    {
      $labelDescriptionFormat = new LabelDescriptionFormat($this->order, $this->orderSettings, $this->deliveryOptions);

      return $labelDescriptionFormat->getFormattedLabelDescription();
    }

    private function setDropOffPoint(): void
    {
        $carrierId     = $this->consignment->getCarrierId();
        $configuration = AccountSettings::getInstance()->getCarrierConfigurationByCarrierId($carrierId);

        if (! $configuration) {
            return;
        }

        $this->consignment->setDropOffPoint($configuration->getDefaultDropOffPoint());
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
