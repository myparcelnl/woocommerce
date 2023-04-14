import {StoreListener, useCheckoutStore} from '@myparcel-woocommerce/frontend-common/src';
import {keepTaxFieldsInSync, showHideTaxFields} from './utils';

jQuery(() => {
  const checkout = useCheckoutStore();

  checkout.on(StoreListener.UPDATE, () => {
    showHideTaxFields();
  });

  keepTaxFieldsInSync();
});
