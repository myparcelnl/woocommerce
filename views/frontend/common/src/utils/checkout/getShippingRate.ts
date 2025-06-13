import {type WcShippingRate} from '../../types';
import {useWcCartStore} from './useWcCartStore';

export const getShippingRate = (): WcShippingRate | undefined => {
  const cartStore = useWcCartStore();
  const shippingRates = cartStore.selectors.getShippingRates();

  return shippingRates?.[0]?.shipping_rates.find((rate) => rate.selected);
};
