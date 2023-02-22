import {FIELD_COUNTRY, getAddressFieldValue, useSettingsStore} from '../';

export const hasSplitAddressFields = (country?: string): boolean => {
  country ??= getAddressFieldValue(FIELD_COUNTRY);

  const settings = useSettingsStore();

  if (!settings.state.hasSplitAddressFields) {
    return false;
  }

  return !!country && settings.state.splitAddressFieldsCountries.includes(country.toUpperCase());
};
