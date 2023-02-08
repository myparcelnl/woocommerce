import {FIELD_COUNTRY, getAddressField, useSettingsStore} from '../';

export const hasSplitAddressFields = (country?: string): boolean => {
  if (!country) {
    const countryField = getAddressField(FIELD_COUNTRY);

    country = countryField?.value;
  }

  const settings = useSettingsStore();

  if (!settings.state.hasSplitAddressFields) {
    return false;
  }

  return !!country && settings.state.splitAddressFieldsCountries.includes(country.toUpperCase());
};
