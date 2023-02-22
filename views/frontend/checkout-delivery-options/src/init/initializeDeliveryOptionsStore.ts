import {EVENT_HIDE_DELIVERY_OPTIONS, EVENT_SHOW_DELIVERY_OPTIONS} from '@myparcel-pdk/checkout/src';
import {
  FrontendAppContext,
  StoreListener,
  objectDiffers,
  triggerEvent,
  useCheckoutStore,
} from '@myparcel-woocommerce/frontend-common';
import {fetchContext} from '../endpoints';
import {getDeliveryOptionsAddress} from '../delivery-options';
import {useDeliveryOptionsStore} from '../store';

export const initializeDeliveryOptionsStore = (context: FrontendAppContext['checkout']): void => {
  const checkout = useCheckoutStore();
  const deliveryOptions = useDeliveryOptionsStore();

  deliveryOptions.set({
    config: context.config,
    strings: context.strings,
    address: getDeliveryOptionsAddress(),
  });

  checkout.on(StoreListener.UPDATE, (newState, oldState) => {
    if (newState.hasDeliveryOptions === oldState.hasDeliveryOptions) {
      return;
    }

    triggerEvent(newState.hasDeliveryOptions ? EVENT_SHOW_DELIVERY_OPTIONS : EVENT_HIDE_DELIVERY_OPTIONS);
    fetchContext();
  });

  checkout.on(StoreListener.UPDATE, (newState, oldState) => {
    if (!objectDiffers(newState.form, oldState.form)) {
      return;
    }

    deliveryOptions.set({address: getDeliveryOptionsAddress()});
  });
};
