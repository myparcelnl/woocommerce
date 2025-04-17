import {type WcShippingRate} from '../../types';
import {useCartStore} from './useCartStore';

export const getShippingRate = (): WcShippingRate | undefined => {
  const cartStore = useCartStore();
  const shippingRates = cartStore.getShippingRates();

  return shippingRates[0].shipping_rates.find((rate) => rate.selected);
};
