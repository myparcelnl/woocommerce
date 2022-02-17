<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin\settings;

use DateTime;
use DateTimeZone;
use WCMYPA_Settings;

/**
 * Service to determine whether the sameDayDelivery option should be displayed in the delivery options widget.
 */
class SameDayDeliveryService
{
    /**
     * @var string
     */
    private $carrierName;

    /**
     * @var
     */
    private $settingsCollection;

    /**
     * @param  string $carrierName
     */
    public function __construct(string $carrierName)
    {
        $this->carrierName        = $carrierName;
        $this->settingsCollection = WCMYPA()->setting_collection->where('carrier', $carrierName);
    }

    /**
     * @return bool
     */
    public function shouldShowSameDayDelivery(): bool
    {
        $settingCollection    = WCMYPA()->setting_collection->where('carrier', $this->carrierName);
        $carrierIsActive      = (bool) $settingCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED);
        $sameDayFromSettings  = (bool) $settingCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SAME_DAY_DELIVERY);

        if (! $carrierIsActive && ! $sameDayFromSettings) {
            return false;
        }

        $isInSameDayTimeSlot      = $this->isInSameDayTimeSlot();
        $hasNoDropOffDelay        = $this->hasNoDropOffDelay();
        $dropOffAvailableTomorrow = $this->isDropOffPossibleTomorrow();

        return $isInSameDayTimeSlot && $hasNoDropOffDelay && $dropOffAvailableTomorrow;
    }

    /**
     * @return bool
     */
    private function isInSameDayTimeSlot(): bool
    {
        $date                            = (new DateTime())->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $sameDayCutoffTimeFromSettings   = $this->settingsCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SAME_DAY_DELIVERY_CUTOFF_TIME);
        $cutOffTimeFromSettings          = $this->settingsCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_CUTOFF_TIME);
        $sameDayCutOffBeforeNormalCutOff = strtotime($sameDayCutoffTimeFromSettings) < strtotime($cutOffTimeFromSettings);

        if (! $sameDayCutOffBeforeNormalCutOff) {
            return false;
        }

        $now                             = $date->getTimestamp() + $date->getOffset();
        $beforeSameDayCutOffTime         = $now < strtotime($sameDayCutoffTimeFromSettings);
        $afterRegularCutOffTime          = strtotime($cutOffTimeFromSettings) < $now;

        return $beforeSameDayCutOffTime || $afterRegularCutOffTime;
    }

    /**
     * @return bool
     */
    private function isDropOffPossibleTomorrow(): bool
    {
        $dropOffDays = $this->settingsCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DAYS);

        return in_array(date('N'), $dropOffDays, true);
    }

    /**
     * @return bool
     */
    private function hasNoDropOffDelay(): bool
    {
        return '0' === $this->settingsCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DELAY);
    }
}
