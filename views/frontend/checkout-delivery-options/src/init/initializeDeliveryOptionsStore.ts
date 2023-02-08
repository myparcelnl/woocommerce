import {
  EVENT_HIDE_DELIVERY_OPTIONS,
  EVENT_SHOW_DELIVERY_OPTIONS,
  FrontendAppContext,
  StoreListener,
  getAddress,
  triggerEvent,
  useCheckoutStore,
  useDeliveryOptionsStore,
} from '@myparcel-woocommerce/frontend-common';
import {fetchContext} from '../delivery-options';

export const initializeDeliveryOptionsStore = (context: FrontendAppContext['checkout']): void => {
  const checkout = useCheckoutStore();
  const deliveryOptions = useDeliveryOptionsStore();

  checkout.on(StoreListener.UPDATE, (state, oldState) => {
    if (state.hasDeliveryOptions !== oldState.hasDeliveryOptions) {
      triggerEvent(state.hasDeliveryOptions ? EVENT_SHOW_DELIVERY_OPTIONS : EVENT_HIDE_DELIVERY_OPTIONS);
      fetchContext();
    }
  });

  deliveryOptions.set({
    config: context.config,
    strings: context.strings,
    address: getAddress(),
  });
};
