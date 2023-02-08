import {shippingMethodHasDeliveryOptions} from './shippingMethodHasDeliveryOptions';
import {useCheckoutStore} from '@myparcel-woocommerce/frontend-common';

/**
 * Hides/shows the delivery options based on the current shipping method. Makes sure to not update the checkout
 *  unless necessary by checking if hasDeliveryOptions is true or false.
 */
export const toggleDeliveryOptions = (shippingMethod: null | string): void => {
  const checkout = useCheckoutStore();
  const hasDeliveryOptions = shippingMethodHasDeliveryOptions(shippingMethod);

  checkout.set({hasDeliveryOptions});
};
