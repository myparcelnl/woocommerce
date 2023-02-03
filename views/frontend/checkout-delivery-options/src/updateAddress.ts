import {getAddress} from './getAddress';
import {useCheckoutStore} from './store';
import {useDeliveryOptionsStore} from './store/useDeliveryOptionsStore';

/**
 * Get data from form fields, put it in the global MyParcelConfig, then trigger updating the delivery options.
 */
export const updateAddress = (): void => {
  const checkout = useCheckoutStore();

  if (checkout.state.hasDeliveryOptions) {
    const deliveryOptions = useDeliveryOptionsStore();

    deliveryOptions.set({address: getAddress()});
  }
};
