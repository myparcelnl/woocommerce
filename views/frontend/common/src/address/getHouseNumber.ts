import {AddressType, FIELD_HOUSE_NUMBER, getAddressFieldValue, getAddressParts} from '../';
import {hasSplitAddressFields} from './hasSplitAddressFields';

/**
 * Get the house number from either the house_number field or the address_1 field. If it's the address field use
 * the split street regex to extract the house number.
 *
 * @returns {String}
 */
export const getHouseNumber = (type?: AddressType): undefined | string => {
  if (hasSplitAddressFields()) {
    const billingNumber = getAddressFieldValue(FIELD_HOUSE_NUMBER, AddressType.BILLING);
    const shippingNumber = getAddressFieldValue(FIELD_HOUSE_NUMBER, AddressType.SHIPPING);

    if (billingNumber || shippingNumber) {
      return getAddressFieldValue(FIELD_HOUSE_NUMBER, type);
    }
  }

  return getAddressParts(type)?.number;
};
