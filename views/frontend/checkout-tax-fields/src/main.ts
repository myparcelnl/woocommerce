import {StoreListener, useCheckoutStore} from '@myparcel-woocommerce/frontend-common/src';
import {toggleTaxFields} from './utils';

jQuery(() => {
  const checkout = useCheckoutStore();

  checkout.on(StoreListener.UPDATE, () => {
    toggleTaxFields();
  });
});
