import {FIELD_COUNTRY} from './data';
import {getAddressField} from './utils';
import {getStoreValue} from './store';

export const hasSplitAddressFields = (country?: string): boolean => {
  if (!country) {
    const countryField = getAddressField(FIELD_COUNTRY);

    country = countryField?.value;
  }

  const hasSplitAddressFields = getStoreValue('hasSplitAddressFields');

  if (!hasSplitAddressFields) {
    return false;
  }

  return window.MyParcelNLData.splitAddressFieldsCountries.includes(country?.toUpperCase());
};
