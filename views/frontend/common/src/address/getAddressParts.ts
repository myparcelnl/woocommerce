import {AddressType, FIELD_ADDRESS, getAddressFieldValue, splitAddress} from '../';

export type AddressParts = {
  street?: string;
  number?: string;
  numberSuffix?: string;
};

export const getAddressParts = (type?: AddressType): AddressParts => {
  const address = getAddressFieldValue(FIELD_ADDRESS, type) as string;

  return splitAddress(address);
};
