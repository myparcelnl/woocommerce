import {AddressType} from './types';
import {FIELD_SHIP_TO_DIFFERENT_ADDRESS} from './data';

export const getAddressType = (): AddressType => {
  let useShipping = false;
  const addressCheckbox: HTMLInputElement | null = document.querySelector(FIELD_SHIP_TO_DIFFERENT_ADDRESS);

  if (addressCheckbox) {
    useShipping = addressCheckbox.checked;
  }

  return useShipping ? 'shipping' : 'billing';
};
