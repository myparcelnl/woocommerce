import {AddressType} from '../types';
import {getElement} from '../utils';
import {useCheckoutStore} from '../store';

/**
 * Get field by name. Will return element with MyParcelFrontend selector: "#<billing|shipping>_<name>".
 */
export const getAddressField = (name: string, addressType?: AddressType): HTMLInputElement | null => {
  addressType ??= useCheckoutStore().state.addressType;

  return getElement(`#${addressType}_${name}`);
};
