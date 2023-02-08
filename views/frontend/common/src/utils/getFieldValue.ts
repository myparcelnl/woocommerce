import {AddressType, getAddressField} from '../';

export const getFieldValue = (name: string, addressType?: AddressType): undefined | string => {
  const field = getAddressField(name, addressType);

  return field?.value;
};
