import {getAddressType, useCheckoutStore} from '@myparcel-woocommerce/frontend-common';
import {getDeliveryOptionsAddress} from '../delivery-options';
import {useDeliveryOptionsStore} from '../store';

/**
 * Get data from form fields, put it in the global MyParcelConfig, then trigger updating the delivery options.
 */
export const updateAddress = (): void => {
  const checkout = useCheckoutStore();

  checkout.set({addressType: getAddressType()});

  if (checkout.state.hasDeliveryOptions) {
    const deliveryOptions = useDeliveryOptionsStore();
    deliveryOptions.set({address: getDeliveryOptionsAddress()});
  }
};
