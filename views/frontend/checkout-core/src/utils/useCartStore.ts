/* eslint-disable @typescript-eslint/naming-convention */
import {type WcShippingRate, type WpStore} from '../types';

export interface WcCartStore extends WpStore {
  dispatch: {
    setShippingAddress(address: Record<string, string>): void;
    setBillingAddress(address: Record<string, string>): void;
  };

  getCustomerData(): {
    shippingAddress: Record<string, string>;
    billingAddress: Record<string, string>;
  };

  getShippingRates(): [{shipping_rates: WcShippingRate[]}];
}

export const useCartStore = (): WcCartStore => {
  const {CART_STORE_KEY} = window.wc.wcBlocksData;

  return {
    ...wp.data.select(CART_STORE_KEY),
    dispatch: wp.data.dispatch(CART_STORE_KEY),
  };
};
