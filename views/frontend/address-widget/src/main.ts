import App from 'address-widget';
import {usePdkCheckout} from '@myparcel-pdk/checkout';

// @TODO import WC styles

const SELECTED_ADDRESS_EVENT = 'address-selected'; // @TODO should be exported by Address Widget
const initializeAddressWidget = () => {
  window.addEventListener(SELECTED_ADDRESS_EVENT, (data: CustomEvent) => {
    // Write incoming data to a form field
    console.log(data);

    // TODO: Refactor the below POC code to a generic, reusable method (field names should come from (PDK) config)
    const countryField = document.querySelector('input[name="billing_country"]');

    if (countryField) {
      countryField.setAttribute('value', data.detail?.countryCode);
    }

    const resolvedField = document.querySelector('input[name="billing_address_resolved"]');

    if (resolvedField) {
      resolvedField.setAttribute('value', JSON.stringify(data.detail));
    }

    const addressLine1 = document.querySelector('input[name="billing_address_1"]');

    if (addressLine1) {
      const addressLineData = [];

      if (data.detail?.street) {
        addressLineData.push(data.detail.street);
      }

      if (data.detail?.houseNumber) {
        addressLineData.push(data.detail.houseNumber);
      }

      if (data.detail?.houseNumberExtension) {
        addressLineData.push(data.detail.houseNumberExtension);
      }

      addressLine1.setAttribute('value', addressLineData.join(' '));
    }

    const postalCode = document.querySelector('input[name="billing_postcode"]');

    if (postalCode) {
      postalCode.setAttribute('value', data.detail?.postalCode);
    }
  });

  App.mount('#form');
};

usePdkCheckout().onInitialize(initializeAddressWidget);
