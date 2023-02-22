import {AddressType, getAddressField} from '../';

export const setFieldValue = (name: string, value?: string, addressType?: AddressType): void => {
  const field = getAddressField(name, addressType);

  if (field) {
    field.value = value ?? '';
  }
};
