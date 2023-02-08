import {AddressType, FIELD_ADDRESS, getFieldValue, splitAddress} from '../';

export type AddressParts = {
  street?: string;
  number?: string;
  numberSuffix?: string;
};

export const getAddressParts = (type?: AddressType): AddressParts => {
  const address = getFieldValue(FIELD_ADDRESS, type);

  return splitAddress(address);
};
