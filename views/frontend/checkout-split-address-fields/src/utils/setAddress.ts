import {AddressType, FIELD_ADDRESS_1, setFieldValue} from '@myparcel-woocommerce/frontend-common';
import {fillAddressFields, splitAddress} from '../address';
import {isOfType} from '@myparcel/ts-utils';

/**
 * Autofill helper for layout with separate number field.
 * Split street to 3 fields on autofill.
 *
 * @param {Event} event
 */
export const setAddress = (event: Event): void => {
  if (!isOfType<HTMLInputElement>(event.target, 'value')) {
    return;
  }

  const addressType = event.target.getAttribute('id')?.search(AddressType.BILLING)
    ? AddressType.BILLING
    : AddressType.SHIPPING;

  fillAddressFields(splitAddress(event.target.value));

  // Fill in the hidden address line 1 field in case a theme forces it to be required.
  setFieldValue(FIELD_ADDRESS_1, event.target.value, addressType);
};
