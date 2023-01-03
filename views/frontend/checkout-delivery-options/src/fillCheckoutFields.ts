import {getAddressField} from './utils/getAddressField';

/**
 * Set the values of the WooCommerce fields. Ignores empty values.
 *
 * @param {Object|null} address - The new address.
 */
export const fillCheckoutFields = (address) => {
  if (!address) {
    return;
  }

  Object.keys(address).forEach((fieldName) => {
    const field = getAddressField(fieldName);
    const value = address[fieldName];

    if (!field || !value) {
      return;
    }

    field.value = value;
  });
};
