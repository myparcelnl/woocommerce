import {FIELD_COUNTRY} from './data';
import {getAddressField} from './utils';

/**
 * Check if the country changed by comparing the old value with the new value before overwriting the MyParcelConfig
 *  with the new value. Returns true if none was set yet.
 *
 * @returns {Boolean}
 */
export const countryHasChanged = (): boolean => {
  if (window.MyParcelConfig.address && window.MyParcelConfig.address.hasOwnProperty('cc')) {
    return window.MyParcelConfig.address.cc !== getAddressField(FIELD_COUNTRY).value;
  }

  return true;
};
