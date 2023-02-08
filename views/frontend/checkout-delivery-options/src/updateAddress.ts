import {
  getAddress,
  getAddressType,
  useCheckoutStore,
  useDeliveryOptionsStore,
} from '@myparcel-woocommerce/frontend-common';

/**
 * Get data from form fields, put it in the global MyParcelConfig, then trigger updating the delivery options.
 */
export const updateAddress = (): void => {
  const checkout = useCheckoutStore();

  checkout.set({addressType: getAddressType()});

  if (checkout.state.hasDeliveryOptions) {
    const deliveryOptions = useDeliveryOptionsStore();
    deliveryOptions.set({address: getAddress()});
  }
};
