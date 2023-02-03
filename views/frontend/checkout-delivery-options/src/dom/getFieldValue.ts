import {AddressType} from '../types';
import {getAddressField} from '../utils';

export const getFieldValue = (name: string, addressType?: AddressType): null | string => {
  const field = getAddressField(name, addressType);

  return field?.value ?? null;
};
