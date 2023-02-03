import {FIELD_COUNTRY} from './data';
import {getAddressField} from './utils';
import {useSettingsStore} from './store';

export const hasSplitAddressFields = (country?: string): boolean => {
  if (!country) {
    const countryField = getAddressField(FIELD_COUNTRY);

    country = countryField?.value;
  }

  const settings = useSettingsStore();

  if (!settings.hasSplitAddressFields) {
    return false;
  }

  return !!country && settings.splitAddressFieldsCountries.includes(country.toUpperCase());
};
