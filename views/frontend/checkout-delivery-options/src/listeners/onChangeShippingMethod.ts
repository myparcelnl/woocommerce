import {toggleDeliveryOptions} from '../delivery-options';

export const onChangeShippingMethod = (oldShippingMethod: null | string, newShippingMethod: null | string): void => {
  toggleDeliveryOptions(newShippingMethod);
  // if (shippingMethodHasDeliveryOptions(newShippingMethod)) {
  //   updateDeliveryOptionsConfig();
  // }
};
