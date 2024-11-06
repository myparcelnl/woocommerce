import {useCartStore, type WcCartStore} from '../utils';
import {type WcAddressObject} from '../types';
import {AddressType} from '../../../../../../../../../myparcelnl/js-pdk/libs/checkout-common';
import {resolveAddress} from './resolveAddress';

export const updateAddressInStore = (addressType: AddressType, newAddress: WcAddressObject): WcAddressObject => {
  const cartStore = useCartStore();

  const storeMethod: keyof WcCartStore['dispatch'] =
    addressType === AddressType.Billing ? 'setBillingAddress' : 'setShippingAddress';

  const mappedAddress = resolveAddress(newAddress);

  cartStore.dispatch[storeMethod](mappedAddress);

  return mappedAddress;
};
