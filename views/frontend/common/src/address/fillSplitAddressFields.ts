import {AddressParts, AddressType, setFieldValue} from '../';

export const fillSplitAddressFields = (address: AddressParts, addressType?: AddressType): void => {
  Object.entries(address).forEach(([fieldName, value]) => {
    if (!value) {
      return;
    }

    setFieldValue(fieldName, value, addressType);
  });
};
