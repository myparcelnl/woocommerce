import {createApp} from 'vue';
import {default as AddressWidgetApp, ADDRESS_SELECTED_EVENT, type AddressEventPayload} from 'mypa-address-widget';
import {EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED} from '@myparcel-woocommerce/frontend-common';
import {usePdkCheckout} from '@myparcel-pdk/checkout';

// @TODO import WC styles
const SHIPPING_ID = 'shipping_address_widget';
const BILLING_ID = 'billing_address_widget';

// TODO: get from PDK if possible
const woocAddressFields = [
  'address_1_field',
  'address_2_field',
  'street_field',
  'city_field',
  'postcode_field',
  'state_field',
];
const woocAddressFieldPrefixes = ['billing_', 'shipping_'];

const onAddressSelected = (event: CustomEvent<AddressEventPayload['detail']>) => {
  const address = event.detail;
  const checkbox = document.querySelector('[name="ship_to_different_address"]') as HTMLInputElement;

  // Check which widget was changed
  if (event.detail.appIdentifier === SHIPPING_ID) {
    // Load the address into a a hidden input. This assumes some backend script, like PHP, will handle the data when the form is submitted.
    writeAddressToFields('shipping_', address);

    if (checkbox?.value !== '1') {
      writeAddressToFields('billing_', address);
    }
  } else if (event.detail.appIdentifier === BILLING_ID) {
    writeAddressToFields('billing_', address);
  }

  // TODO: Write to a hidden input field with the whole json object
};

/**
 * Writes a MyPa address object to individual wooc fields
 * @param prefix
 * @param address
 */
const writeAddressToFields = (prefix: string, address: AddressEventPayload['detail']) => {
  woocAddressFields.forEach((field) => {
    const fieldId = `${prefix}${field}`;
    const woocFieldInput = document.querySelector(`#${fieldId} input`);

    if (!woocFieldInput) {
      console.warn(`Could not find field ${fieldId}`);
      return;
    }

    const address1Values = [address.street, address.houseNumber, address.houseNumberSuffix].filter(
      (val) => val?.length && val.length > 0,
    );

    switch (field) {
      // TODO: Replace by an actual mapping object
      case 'address_1_field':
        (woocFieldInput as HTMLInputElement).value = address1Values.join(' ');
        break;
      case 'address_2_field':
        // nope
        break;
      case 'street_field':
        (woocFieldInput as HTMLInputElement).value = address.street;
        break;
      case 'city_field':
        (woocFieldInput as HTMLInputElement).value = address.city;
        break;
      case 'postcode_field':
        (woocFieldInput as HTMLInputElement).value = address.postalCode;
        break;
      case 'state_field':
        // nope
        break;
      default:
    }
  });
};

const hideAddressFields = (prefix: string) => {
  woocAddressFields.forEach((field) => {
    const woocField = document.querySelector(`#${prefix}${field}`);

    if (woocField) {
      (woocField as HTMLElement).classList.add('address-widget-forced-hidden');
    }
  });

  // TODO: temporary: show the widget
  if (prefix === 'billing_') {
    document.querySelector(`#${BILLING_ID}`).style.display = 'block';
  } else if (prefix === 'shipping_') {
    document.querySelector(`#${SHIPPING_ID}`).style.display = 'block';
  }
};

const showAddressFields = (prefix: string) => {
  woocAdressFields.forEach((field) => {
    const woocField = document.querySelector(`#${prefix}${field}`);

    if (woocField) {
      (woocField as HTMLElement).style.display = 'block';
    }
  });

  // TODO: temporary: hide the widget
  if (prefix === 'billing_') {
    document.querySelector(`#${BILLING_ID}`).style.display = 'none';
  } else if (prefix === 'shipping_') {
    document.querySelector(`#${SHIPPING_ID}`).style.display = 'none';
  }
};

const woocCountryField = 'country';

const initializeAddressWidget = () => {
  if (!window) {
    return;
  }

  // Factory function to create a new Vue app instance
  // This is necessary because we need to mount the app on two different elements
  const app = (config) => createApp(AddressWidgetApp, {config});
  const baseConfig = window.TemporaryMyParcelAddressConfig; // TODO: replace/make better
  // Foo
  // Mount on billing
  app({
    country: 'NL',
    appIdentifier: BILLING_ID,
    ...baseConfig,
  }).mount(`#${BILLING_ID}`);
  // Mount on shipping
  app({
    country: 'NL',
    appIdentifier: BILLING_ID,
    ...baseConfig,
  }).mount(`#${SHIPPING_ID}`);

  // WooCommerce address fields without the billing and shipping prefix

  // For each of the prefixed country fields, get the current value and add a listener for changes
  woocAddressFieldPrefixes.forEach((prefix) => {
    const countryField = document.querySelector(`select[name="${prefix}${woocCountryField}"]`);

    if (countryField) {
      // Get the current value
      const currentValue = (countryField as HTMLInputElement).value;
      window.TemporaryMyParcelAddressConfig.country = currentValue;
      // TODO: Address widget does not track the window object in real time atm and does not respond to these changes

      // If the current value is NL, hide the address fields, if not, show them
      if (currentValue === 'NL') {
        hideAddressFields(prefix);
      } else {
        showAddressFields(prefix);
      }

      // Add a listener for changes to the field
      // countryField.addEventListener('change', (e) => {
      //   // Get the new value of the field
      //   const newValue = (e.target as HTMLInputElement).value;
      //   console.log(e);

      //   // If the new value is different from the current value, update the address widget config
      //   // TODO: Declare window.MyParcelAddressConfig type somewhere
      //   window.MyParcelAddressConfig.country = newValue;
      //   // TODO: Unhide the woocAdressFields for the curent prefix
      // });
    }

    // TODO: This is SHIPPING ONLY, figure out how to track select2 field in billing
    jQuery(document.body).on(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, (event: Event, newCountry: string) => {
      // TODO: Not sure if this is the right way to do this and how to seperate billing/shipping here, but it'll do...
      window.TemporaryMyParcelAddressConfig.country = newCountry;

      // If the country is NL, hide the address fields, if not, show them
      // TODO: dedupe
      if (newCountry === 'NL') {
        hideAddressFields('shipping_');
        hideAddressFields('billing_');
      } else {
        showAddressFields('shipping_');
        showAddressFields('billing_');
      }
    });
  });

  // Listen for changes to the address
  document.addEventListener(ADDRESS_SELECTED_EVENT, onAddressSelected);
};

usePdkCheckout().onInitialize(initializeAddressWidget);
