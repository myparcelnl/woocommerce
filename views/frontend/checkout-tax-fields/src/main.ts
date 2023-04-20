import {initializeCheckoutTaxFields, usePdkCheckout} from '@myparcel-pdk/checkout/src';

usePdkCheckout().onInitialize(async () => {
  await initializeCheckoutTaxFields();
});
