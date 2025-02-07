import {
  useEvent,
  usePdkCheckout,
} from '@myparcel-pdk/checkout';

import { App } from 'address-widget';


// @TODO import WC styles

const SELECTED_ADDRESS_EVENT = 'address-selected'; // @TODO should be exported by Address Widget
const initializeAddressWidget = () => {
  window.addEventListener(SELECTED_ADDRESS_EVENT, (data) => {
    // Write incoming data to a form field
    console.log(data);
  });

  App.mount('#form');
};

usePdkCheckout().onInitialize(initializeAddressWidget);
