import {initializeCheckoutTaxFields, usePdkCheckout} from '@myparcel-pdk/checkout';

usePdkCheckout().onInitialize(initializeCheckoutTaxFields);
