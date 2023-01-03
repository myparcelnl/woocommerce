import {FIELD_CITY, FIELD_POSTCODE} from '../data';
import {getAddressField} from '../utils';
import {setHouseNumber} from '../setHouseNumber';

/**
 * Set the values of the WooCommerce fields from delivery options data.
 *
 * @param {?Object} address - The new address.
 * @param {String} address.postalCode
 * @param {String} address.city
 * @param {String} address.number
 */
export const setAddressFromDeliveryOptions = (address = null) => {
  address = address || {};

  if (address.postalCode) {
    getAddressField(FIELD_POSTCODE).value = address.postalCode;
  }

  if (address.city) {
    getAddressField(FIELD_CITY).value = address.city;
  }

  if (address.number) {
    setHouseNumber(address.number);
  }
};
