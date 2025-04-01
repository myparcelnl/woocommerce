import {CONFIGURATION_UPDATE_EVENT, type AddressEventPayload, type Alpha2CountryCode} from 'mypa-address-widget';
import {woocAddressFields} from '../constants/fields';
import {hideAddressFields, showAddressFields} from './showHide';
import {BILLING_ID, getConfig, SHIPPING_ID} from './init';

export const handleCountryChange = (event: Event, newCountry: string, wrapper: unknown): void => {
  // TODO: Not sure if this is the right way to do this and how to seperate billing/shipping here, but it'll do...
  updateWidgetCountry(newCountry, wrapperToAppIdentifier(wrapper));

  // If the country is NL, hide the address fields, if not, show them
  // TODO: dedupe
  if (newCountry === 'NL') {
    hideAddressFields();
  } else {
    showAddressFields();
  }
};

/**
 * React to the address selected event from the address widget
 * @param event
 */
export const syncAddressWhenSelected = (event: AddressEventPayload) => {
  const address = event.detail;
  const shipToDifferentAdressCheckbox = document.querySelector(
    '[name="ship_to_different_address"]',
  ) as HTMLInputElement;

  // Check which widget was changed
  if (event.detail.appIdentifier === SHIPPING_ID) {
    // Load the address into a a hidden input. This assumes some backend script, like PHP, will handle the data when the form is submitted.
    writeAddressToFields('shipping_', address);

    if (shipToDifferentAdressCheckbox?.value !== '1') {
      writeAddressToFields('billing_', address);
    }
  } else if (event.detail.appIdentifier === BILLING_ID) {
    writeAddressToFields('billing_', address);
  }
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
      console.debug(`Could not find field ${fieldId}`);
      return;
    }

    const address1Values = [address.street, address.houseNumber, address.houseNumberSuffix].filter(
      (val) => val?.length && val.length > 0,
    );

    // TODO: Write to a hidden input field with the whole json object

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

/**
 * Helper function to determine the currently selected country in WooCommerce
 * @returns
 */
export const getSelectedCountry = (): Alpha2CountryCode => {
  const element = document.querySelector('select.country_to_state');
  return (element as HTMLSelectElement)?.value as Alpha2CountryCode;
};

export const wrapperToAppIdentifier = (wrapper?: any[]): string => {
  // Interpret wrapper as DOM element
  const wrapperElement = wrapper?.[0] as HTMLElement;
  const wrapperClass = wrapperElement?.className;
  console.log(wrapperClass);

  return wrapperClass === 'woocommerce-shipping-fields' ? SHIPPING_ID : BILLING_ID;
};

const updateWidgetCountry = (country: string, appIdentifier: string) => {
  document.dispatchEvent(
    new CustomEvent(CONFIGURATION_UPDATE_EVENT, {
      detail: {appIdentifier, config: {country}},
    }),
  );
};
