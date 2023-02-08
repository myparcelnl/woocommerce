import {AddressType} from '../';
import {createStore} from './createStore';

type CheckoutData = {
  addressType?: AddressType;
  hasDeliveryOptions: boolean;
  hiddenInput?: HTMLInputElement;
  shippingMethod?: string;
};

export const useCheckoutStore = createStore<CheckoutData>('checkout', () => {
  return {
    state: {
      hasDeliveryOptions: false,
    },
  };
});
