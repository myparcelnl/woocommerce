import {AddressType, setFieldValue} from '@myparcel-woocommerce/frontend-common';
import {AddressParts} from './getAddressParts';

export const fillAddressFields = (address: AddressParts, addressType?: AddressType): void => {
  Object.entries(address).forEach(([fieldName, value]) => {
    if (!value) {
      return;
    }

    setFieldValue(fieldName, value, addressType);
  });
};
