import {
  AddressType,
  FIELD_ADDRESS,
  fillSplitAddressFields,
  setFieldValue,
  splitAddress,
} from '@myparcel-woocommerce/frontend-common/src';
import {isOfType} from '@myparcel/ts-utils';

/**
 * Autofill helper for layout with separate number field.
 * Split street to 3 fields on autofill.
 *
 * @param {Event} event
 */
export const setAddress = (event: Event): void => {
  const element = event.target;

  if (!isOfType<HTMLInputElement>(element, 'value')) {
    return;
  }

  const type = element.getAttribute('id')?.search(AddressType.BILLING) ? AddressType.BILLING : AddressType.SHIPPING;

  fillSplitAddressFields(splitAddress(element.value));

  // Fill in the hidden address line 1 field in case a theme forces it to be required.
  setFieldValue(FIELD_ADDRESS, element.value, type);
};
