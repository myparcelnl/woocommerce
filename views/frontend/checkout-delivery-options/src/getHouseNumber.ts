import {hasSplitAddressFields} from './hasSplitAddressFields';
import {getAddressField} from './utils/getAddressField';
import {getAddressParts} from './getAddressParts';
import {FIELD_HOUSE_NUMBER} from './data/fields';

/**
 * Get the house number from either the house_number field or the address_1 field. If it's the address field use
 * the split street regex to extract the house number.
 *
 * @returns {String}
 */
export const getHouseNumber = (): string => {
  const hasBillingNumber = jQuery(`#billing_${FIELD_HOUSE_NUMBER}`).val() !== '';
  const hasShippingNumber = jQuery(`#shipping_${FIELD_HOUSE_NUMBER}`).val() !== '';
  const hasNumber = hasBillingNumber || hasShippingNumber;

  if (hasSplitAddressFields() && hasNumber) {
    const houseNumberField = getAddressField(FIELD_HOUSE_NUMBER);

    return houseNumberField?.value;
  }

  return getAddressParts().house_number;
};
