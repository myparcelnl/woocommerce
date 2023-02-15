import {EVENT_UPDATE_CONFIG, EVENT_UPDATE_DELIVERY_OPTIONS} from '@myparcel-pdk/checkout/src';
import {StoreListener, createStore, objectDiffers, triggerEvent} from '@myparcel-woocommerce/frontend-common';
import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';

type ToRecord<T, k extends keyof T = keyof T> = Record<k, T[k]>;

type DeliveryOptionsStore = ToRecord<MyParcelDeliveryOptions.Configuration>;

export const useDeliveryOptionsStore = createStore<DeliveryOptionsStore>('deliveryOptions', () => {
  return {
    state: {
      config: {},
      strings: {},
      address: {},
    },

    listeners: {
      [StoreListener.UPDATE]: [
        (newState, oldState) => {
          const isNotRendered = document.querySelector('#myparcel-delivery-options');

          if (isNotRendered) {
            triggerEvent(EVENT_UPDATE_DELIVERY_OPTIONS, newState);
            return;
          }

          if (objectDiffers(newState.config, oldState.config)) {
            triggerEvent(EVENT_UPDATE_CONFIG, newState);
          } else if (objectDiffers(newState.address, oldState.address)) {
            triggerEvent(EVENT_UPDATE_DELIVERY_OPTIONS, newState);
          }
        },
      ],
    },
  };
});
