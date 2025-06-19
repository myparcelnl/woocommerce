import {type UseWcStore, type WcCheckoutStore} from '../../types';

export const useWcCheckoutStore = (): UseWcStore<WcCheckoutStore> => {
  // eslint-disable-next-line @typescript-eslint/naming-convention
  const {CHECKOUT_STORE_KEY} = window.wc.wcBlocksData as {CHECKOUT_STORE_KEY: StoreDescriptor};
  return {
    storeDescriptor: CHECKOUT_STORE_KEY,
    selectors: wp.data.select(CHECKOUT_STORE_KEY),
    actions: wp.data.dispatch(CHECKOUT_STORE_KEY),
  };
};
