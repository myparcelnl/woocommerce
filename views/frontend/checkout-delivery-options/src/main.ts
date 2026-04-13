import '../assets/scss/index.scss';
import {usePdkCheckout} from '@myparcel-dev/pdk-checkout';
import {initializeCheckoutDeliveryOptions} from './utils';

usePdkCheckout().onInitialize(initializeCheckoutDeliveryOptions);
