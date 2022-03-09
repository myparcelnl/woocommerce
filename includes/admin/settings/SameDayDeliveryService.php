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
     * @var \WPO\WC\MyParcel\Collections\SettingsCollection
     */
    private $settingsCollection;

    /**
     * @param  string $carrierName
     */
    public function __construct(string $carrierName)
    {
        $this->settingsCollection = WCMYPA()->setting_collection->where('carrier', $carrierName);
    }

    /**
     * @return bool
     */
    public function shouldShowSameDayDelivery(): bool
    {
        $carrierIsActive     = (bool) $this->settingsCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED);
        $sameDayFromSettings = (bool) $this->settingsCollection->getByName(
            WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SAME_DAY_DELIVERY
        );

        if (! $carrierIsActive || ! $sameDayFromSettings) {
            return false;
        }

        $date = (new DateTime())->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $now  = $date->getTimestamp() + $date->getOffset();

        return $this->isInSameDayTimeSlot($now) && ! $this->hasDropOffDelay() && $this->isDropOffPossible($now);
    }

    /**
     * @param  int $now
     *
     * @return bool
     */
    private function isInSameDayTimeSlot(int $now): bool
    {
        $sameDayCutoffTimeFromSettings   = $this->settingsCollection->getByName(
            WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SAME_DAY_DELIVERY_CUTOFF_TIME
        );
        $cutOffTimeFromSettings          = $this->settingsCollection->getByName(
            WCMYPA_Settings::SETTING_CARRIER_CUTOFF_TIME
        );
        $sameDayCutOffBeforeNormalCutOff = strtotime($sameDayCutoffTimeFromSettings) < strtotime($cutOffTimeFromSettings);

        if (! $sameDayCutOffBeforeNormalCutOff) {
            return false;
        }

        $beforeSameDayCutOffTime = $now < strtotime($sameDayCutoffTimeFromSettings);
        $afterRegularCutOffTime  = strtotime($cutOffTimeFromSettings) < $now;

        return $beforeSameDayCutOffTime || $afterRegularCutOffTime;
    }

    /**
     * @param  int $now
     *
     * @return bool
     */
    private function isDropOffPossible(int $now): bool
    {
        $cutOffTime  = $this->settingsCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_CUTOFF_TIME);
        $dropOffDays = $this->settingsCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DAYS);

        if ($now > $cutOffTime) {
            return $this->isDropOffTomorrowPossible($dropOffDays);
        }

        return $this->isDropOffTodayPossible($dropOffDays);
    }

    /**
     * @param  array $dropOffDays
     *
     * @return bool
     */
    private function isDropOffTodayPossible(array $dropOffDays): bool
    {
        return in_array((date('N')), $dropOffDays, true);
    }

    /**
     * @param  array $dropOffDays
     *
     * @return bool
     */
    private function isDropOffTomorrowPossible(array $dropOffDays): bool
    {
        $tomorrow = new DateTime('tomorrow');
        $dayOfTomorrowAsNumber = $tomorrow->format('N');
        return in_array($dayOfTomorrowAsNumber, $dropOffDays, true);
    }

    /**
     * @return bool
     */
    private function hasDropOffDelay(): bool
    {
        return $this->settingsCollection->getByName(WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DELAY);
    }
}
