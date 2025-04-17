import {usePdkCheckout} from '@myparcel-pdk/checkout';
import {initializeAddressWidget} from './utils/init';
// Import the styles
import '../assets/css/style.css';

usePdkCheckout().onInitialize(initializeAddressWidget);
