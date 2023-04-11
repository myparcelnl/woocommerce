import {EVENT_UPDATED_DELIVERY_OPTIONS} from '@myparcel-pdk/checkout/src';
import {showHideTaxFields} from './utils';
import {EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED} from '@myparcel-woocommerce/frontend-common';

document.addEventListener(EVENT_UPDATED_DELIVERY_OPTIONS, (e) => {
  showHideTaxFields(e.detail.carrier);
});

document.addEventListener(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, () => {
  showHideTaxFields(null);
});
