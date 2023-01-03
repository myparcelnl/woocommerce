import {FIELD_HIGHEST_SHIPPING_CLASS} from './data';
import {getStoreValue} from './store';

export const getSelectedShippingMethod = (): string | null => {
  let shippingMethod = getStoreValue('shippingMethod');

  if (shippingMethod === 'flat_rate') {
    shippingMethod += `:${document.querySelectorAll(FIELD_HIGHEST_SHIPPING_CLASS).length}`;
  }

  return shippingMethod;
};
