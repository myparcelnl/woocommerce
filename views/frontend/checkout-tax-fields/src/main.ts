import {initializeCheckoutTaxFields, usePdkCheckout} from '@myparcel-dev/pdk-checkout';

usePdkCheckout().onInitialize(initializeCheckoutTaxFields);
