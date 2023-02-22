import {AddressType, getFieldValue, useCheckoutStore} from '@myparcel-woocommerce/frontend-common';

export const getAddressFieldValue = (name: string, addressType?: AddressType): undefined | string => {
  addressType ??= useCheckoutStore().state.addressType;

  return getFieldValue(`${addressType}_${name}`);
};
