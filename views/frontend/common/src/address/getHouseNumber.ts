import {AddressType, FIELD_HOUSE_NUMBER, getAddressField, getAddressParts, getFieldValue} from '../';
import {hasSplitAddressFields} from './hasSplitAddressFields';

/**
 * Get the house number from either the house_number field or the address_1 field. If it's the address field use
 * the split street regex to extract the house number.
 *
 * @returns {String}
 */
export const getHouseNumber = (type?: AddressType): undefined | string => {
  if (hasSplitAddressFields()) {
    const billingNumber = getAddressField(FIELD_HOUSE_NUMBER, AddressType.BILLING);
    const shippingNumber = getAddressField(FIELD_HOUSE_NUMBER, AddressType.SHIPPING);

    const hasBillingNumber = billingNumber?.value !== '';
    const hasShippingNumber = shippingNumber?.value !== '';

    const hasNumber = hasBillingNumber || hasShippingNumber;

    if (hasNumber) {
      return getFieldValue(FIELD_HOUSE_NUMBER, type);
    }
  }

  return getAddressParts(type)?.number;
};
