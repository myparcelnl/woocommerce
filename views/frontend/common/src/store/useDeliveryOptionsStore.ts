import {EVENT_UPDATE_CONFIG, EVENT_UPDATE_DELIVERY_OPTIONS} from '../data';
import {objectDiffers, triggerEvent} from '../';
import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';
import {createStore} from './createStore';

type ToRecord<T, k extends keyof T = keyof T> = Record<k, T[k]>;

type DeliveryOptionsStore = ToRecord<MyParcelDeliveryOptions.Configuration>;

export const useDeliveryOptionsStore = createStore<DeliveryOptionsStore>('deliveryOptions', () => {
  return {
    state: {
      config: {},
      strings: {},
      address: {},
    },

    onUpdate: (newState: DeliveryOptionsStore, oldState: DeliveryOptionsStore) => {
      console.log('%cDELIVERY OPTIONS', 'color: #4dc', 'deliveryOptions', {newState, oldState});

      if (document.querySelector('#myparcel-delivery-options')) {
        triggerEvent(EVENT_UPDATE_DELIVERY_OPTIONS, newState);
        return;
      }

      if (objectDiffers(newState.config, oldState.config)) {
        triggerEvent(EVENT_UPDATE_CONFIG, newState);
      } else if (objectDiffers(newState.address, oldState.address)) {
        triggerEvent(EVENT_UPDATE_DELIVERY_OPTIONS, newState);
      }
    },
  };
});
