import {shippingMethodHasDeliveryOptions} from '../delivery-options';
import {useCheckoutStore} from '../store';

export const onChangeShippingMethod = (oldShippingMethod: null | string, newShippingMethod: null | string): void => {
  const checkout = useCheckoutStore();

  checkout.set({
    hasDeliveryOptions: shippingMethodHasDeliveryOptions(newShippingMethod),
  });

  // if (shippingMethodHasDeliveryOptions(newShippingMethod)) {
  //   updateDeliveryOptionsConfig();
  // }
};
