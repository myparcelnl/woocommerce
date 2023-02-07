import {EVENT_UPDATE_CONFIG, EVENT_UPDATE_DELIVERY_OPTIONS} from '../data';
import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';
import {createStore} from './createStore';
import {objectDiffers} from '../utils/objectDiffers';
import {triggerEvent} from '../triggerEvent';

type ToRecord<T extends MyParcelDeliveryOptions.Configuration, k extends keyof T = keyof T> = Record<k, T[k]>;

type DeliveryOptionsContext = ToRecord<MyParcelDeliveryOptions.Configuration>;

export const useDeliveryOptionsStore = createStore<DeliveryOptionsContext>('deliveryOptions', () => {
  return {
    state: {
      config: {},
      strings: {},
      address: {},
    },

    onUpdate: (newState: DeliveryOptionsContext, oldState: DeliveryOptionsContext) => {
      console.log('%cDELIVERY OPTIONS', 'color: #4dc', 'deliveryOptions', { newState, oldState });

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
