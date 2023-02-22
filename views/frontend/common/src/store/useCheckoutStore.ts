import {AddressType, FIELD_SHIP_TO_DIFFERENT_ADDRESS} from '../';
import {createStore} from './createStore';
import {shippingMethodHasDeliveryOptions} from '@myparcel-woocommerce/frontend-checkout-delivery-options/src/delivery-options';

type CheckoutData = {
  addressType?: AddressType;
  hasDeliveryOptions: boolean;
  hiddenInput?: HTMLInputElement;
  shippingMethod?: string;
  form: Record<string, undefined | string>;
};

export const useCheckoutStore = createStore<CheckoutData>('checkout', () => {
  return {
    state: {
      hasDeliveryOptions: false,
      form: {},
    },

    listeners: {
      update: [
        (newState, oldState) => {
          if (newState.form[FIELD_SHIP_TO_DIFFERENT_ADDRESS] === oldState.form[FIELD_SHIP_TO_DIFFERENT_ADDRESS]) {
            return;
          }

          const shipToDifferentAddress = newState.form[FIELD_SHIP_TO_DIFFERENT_ADDRESS] === '1';

          newState.addressType = shipToDifferentAddress ? AddressType.SHIPPING : AddressType.BILLING;
        },

        (newState, oldState) => {
          const newShippingMethod = newState.form['shipping_method[0]'];

          if (newShippingMethod === oldState.form['shipping_method[0]']) {
            return;
          }

          newState.shippingMethod = newShippingMethod;
          newState.hasDeliveryOptions = Boolean(
            newShippingMethod && shippingMethodHasDeliveryOptions(newShippingMethod),
          );

          return newState;
        },
      ],
    },
  };
});
