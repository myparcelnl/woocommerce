import {AddressType, FIELD_ADDRESS_1, getAddressFieldValue} from '@myparcel-woocommerce/frontend-common';
import {splitAddress} from './splitAddress';

export type AddressParts = {
  street?: string;
  number?: string;
  numberSuffix?: string;
};

export const getAddressParts = (type?: AddressType): AddressParts => {
  const address = getAddressFieldValue(FIELD_ADDRESS_1, type) as string;

  return splitAddress(address);
};
