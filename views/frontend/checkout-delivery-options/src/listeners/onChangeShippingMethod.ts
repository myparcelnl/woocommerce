import {shippingMethodHasDeliveryOptions} from '../delivery-options';
import {useCheckoutStore} from '@myparcel-woocommerce/frontend-common';

export const onChangeShippingMethod = (newShippingMethod?: string): void => {
  const checkout = useCheckoutStore();

  if (checkout.state.shippingMethod === newShippingMethod) {
    return;
  }

  checkout.set({
    hasDeliveryOptions: shippingMethodHasDeliveryOptions(newShippingMethod),
    shippingMethod: newShippingMethod,
  });
};
