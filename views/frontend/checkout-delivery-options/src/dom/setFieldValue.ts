import {getAddressField} from '../utils';

export const setFieldValue = (name: string, value: string) => {
  const field = getAddressField(name);

  if (field) {
    field.value = value;
  }
};
