import {AddressType} from '../types';
import {getAddressType} from '../getAddressType';

/**
 * Get field by name. Will return element with MyParcelFrontend selector: "#<billing|shipping>_<name>".
 */
export const getAddressField = (name: string, addressType?: AddressType): HTMLInputElement | null => {
  if (!addressType) {
    addressType = getAddressType();
  }

  const selector = `#${addressType}_${name}`;
  const field: HTMLInputElement | null = document.querySelector(selector);

  if (!field) {
    // eslint-disable-next-line no-console
    console.warn(`Field ${selector} not found.`);
  }

  return field;
};
