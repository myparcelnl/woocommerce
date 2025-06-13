import {type StoreDescriptor} from '@wordpress/data';
import {type WcCartStore, type UseWcStore} from '../../types';

export const useWcCartStore = (): UseWcStore<WcCartStore> => {
  const {CART_STORE_KEY} = window.wc.wcBlocksData as {CART_STORE_KEY: StoreDescriptor};
  return {
    storeDescriptor: CART_STORE_KEY,
    selectors: wp.data.select(CART_STORE_KEY),
    actions: wp.data.dispatch(CART_STORE_KEY),
  };
};
