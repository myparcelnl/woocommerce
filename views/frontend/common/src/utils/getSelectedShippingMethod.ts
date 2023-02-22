import {FIELD_HIGHEST_SHIPPING_CLASS, useCheckoutStore} from '../';

export const getSelectedShippingMethod = (): undefined | string => {
  const checkout = useCheckoutStore();

  let {shippingMethod} = checkout.state;

  if (shippingMethod === 'flat_rate') {
    shippingMethod += `:${document.querySelectorAll(FIELD_HIGHEST_SHIPPING_CLASS).length}`;
  }

  return shippingMethod;
};
