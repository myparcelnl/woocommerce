import '../assets/scss/index.scss';
import {EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED} from '@myparcel-woocommerce/frontend-common/src';
import {setAddress, synchronizeAddress} from './utils';

jQuery(() => {
  console.log('[tax-fields:START]');
  // hide the tax fields except:
  // europlus + gb: show eori
  // europlus + customs country: show vat

  console.log('[tax-fields:END]');
});
