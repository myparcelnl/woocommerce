import {FIELD_HIGHEST_SHIPPING_CLASS} from './data';
import {useCheckoutStore} from './store';

export const getSelectedShippingMethod = (): string | null => {
  const checkout = useCheckoutStore();

  let {shippingMethod} = checkout.state;

  if (shippingMethod === 'flat_rate') {
    shippingMethod += `:${document.querySelectorAll(FIELD_HIGHEST_SHIPPING_CLASS).length}`;
  }

  return shippingMethod;
};
