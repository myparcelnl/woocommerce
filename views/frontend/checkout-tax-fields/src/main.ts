import {StoreListener, useCheckoutStore} from '@myparcel-woocommerce/frontend-common';
import {keepTaxFieldsInSync, showHideTaxFields} from './utils';

const store = useCheckoutStore();

store.on(StoreListener.UPDATE, () => {
  showHideTaxFields();
});

keepTaxFieldsInSync();
