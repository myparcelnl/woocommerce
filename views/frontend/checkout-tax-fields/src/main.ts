import {StoreListener, useCheckoutStore} from '@myparcel-woocommerce/frontend-common';
import {showHideTaxFields} from './utils';

const store = useCheckoutStore();

store.on(StoreListener.UPDATE, () => {
  showHideTaxFields();
});
