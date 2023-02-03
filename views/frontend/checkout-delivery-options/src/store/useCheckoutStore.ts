import {AddressType} from '../types';
import {createStore} from './createStore';
import {triggerEvent} from '../triggerEvent';
import {EVENT_HIDE_DELIVERY_OPTIONS, EVENT_SHOW_DELIVERY_OPTIONS} from '../data';
import {updateDeliveryOptionsConfig} from '../delivery-options';

type CheckoutData = {
  addressType: AddressType | null;
  hasDeliveryOptions: boolean;
  shippingMethod: string | null;
};

export const useCheckoutStore = createStore<CheckoutData>('checkout', () => {
  return {
    state: {
      hasDeliveryOptions: false,
      addressType: null,
      shippingMethod: null,
    },

    onUpdate: (state, newState) => {
      if (state.hasDeliveryOptions !== newState.hasDeliveryOptions) {
        triggerEvent(state.hasDeliveryOptions ? EVENT_SHOW_DELIVERY_OPTIONS : EVENT_HIDE_DELIVERY_OPTIONS);
        updateDeliveryOptionsConfig();
      }
    },
  };
});
