/* eslint-disable @typescript-eslint/naming-convention */
import {type WcShippingRate, type WpStore} from '../types';

export interface WcCartStore extends WpStore {
  getCustomerData(): {
    shippingAddress: Record<string, string>;
  };

  getShippingRates(): [{shipping_rates: WcShippingRate[]}];
}

export const useCartStore = (): WcCartStore => {
  const {CART_STORE_KEY} = window.wc.wcBlocksData;

  return wp.data.select(CART_STORE_KEY);
};
