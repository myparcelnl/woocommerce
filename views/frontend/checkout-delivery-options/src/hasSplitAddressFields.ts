import {FIELD_COUNTRY} from './data';
import {getAddressField} from './utils';

export const hasSplitAddressFields = (country?: string): boolean => {
  if (!country) {
    const countryField = getAddressField(FIELD_COUNTRY);

    country = countryField?.value;
  }

  if (!window.MyParcelNLData.isUsingSplitAddressFields) {
    return false;
  }

  return window.MyParcelNLData.splitAddressFieldsCountries.includes(country?.toUpperCase());
};
