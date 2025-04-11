import {CONFIGURATION_UPDATE_EVENT, type AddressEventPayload, type Alpha2CountryCode} from 'mypa-address-widget';
import {HIDDEN_ADDRESS_FIELD, WOOC_ADDRESS_FIELDS} from '../constants/fields';
import {getClassicCheckoutConfig} from '../../../checkout-core/src/classic';
import {hideAddressFields, showAddressFields} from './showHide';
import {BILLING_ID, SHIPPING_ID} from './init';

export const createHiddenInput = (prefix: string): HTMLInputElement => {
  const hiddenInput = document.createElement('input');
  hiddenInput.type = 'hidden';
  hiddenInput.name = `${prefix}${HIDDEN_ADDRESS_FIELD}`;
  hiddenInput.id = `${prefix}${HIDDEN_ADDRESS_FIELD}`;
  hiddenInput.value = JSON.stringify({});

  // Add it to the form
  const form = document.querySelector('form.woocommerce-checkout');

  if (!form) {
    console.warn(`Failed to add ${hiddenInput.id} to the form, failed to find the form.`);
    return hiddenInput;
  }

  form.appendChild(hiddenInput);
  return hiddenInput;
};

export const handleCountryChange = (event: Event, newCountry: string, wrapper: unknown[]): void => {
  updateWidgetCountry(newCountry, wrapperToAppIdentifier(wrapper));

  // If the country is NL, hide the address fields, if not, show them
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
export const syncAddressWhenSelected = (event: AddressEventPayload): void => {
  const address = event.detail;
  const shipToDifferentAdressCheckbox = document.querySelector(
    '[name="ship_to_different_address"]',
  ) as HTMLInputElement;

  // Check which widget was changed
  if (event.detail.appIdentifier === SHIPPING_ID) {
    writeAddressToFields(getClassicCheckoutConfig().prefixShipping, address);

    if (shipToDifferentAdressCheckbox?.value !== '1') {
      writeAddressToFields(getClassicCheckoutConfig().prefixBilling, address);
    }
  } else if (event.detail.appIdentifier === BILLING_ID) {
    writeAddressToFields(getClassicCheckoutConfig().prefixBilling, address);
  }
};

/**
 * Writes a MyPa address object to individual wooc fields
 * @param prefix
 * @param address
 */
const writeAddressToFields = (prefix: string, address: AddressEventPayload['detail']) => {
  // Write the address to the hidden input field
  const hiddenInput = document.querySelector(`#${prefix}${HIDDEN_ADDRESS_FIELD}`) as HTMLInputElement;
  hiddenInput.value = JSON.stringify(address);

  WOOC_ADDRESS_FIELDS.forEach((field) => {
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
  return wrapperClass === 'woocommerce-shipping-fields' ? SHIPPING_ID : BILLING_ID;
};

const updateWidgetCountry = (country: string, appIdentifier: string) => {
  document.dispatchEvent(
    new CustomEvent(CONFIGURATION_UPDATE_EVENT, {
      detail: {appIdentifier, config: {country}},
    }),
  );
};
