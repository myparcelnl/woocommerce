import {CONFIGURATION_UPDATE_EVENT, type AddressEventPayload, type Alpha2CountryCode} from 'mypa-address-widget';
import {getClassicCheckoutConfig, isClassicCheckout} from '@myparcel-woocommerce/frontend-common';
import {useSettings} from '@myparcel-pdk/checkout';
import {ALL_ADDRESS_FIELDS} from '../constants/fields';
import {hideAddressFields, showAddressFields} from './showHide';
import {BILLING_ID, SHIPPING_ID} from './init';

export const createHiddenInput = (prefix: string): HTMLInputElement => {
  const HIDDEN_ADDRESS_FIELD = useSettings().checkoutAddressHiddenInputName;
  const hiddenInput = document.createElement('input');
  hiddenInput.type = 'hidden';
  hiddenInput.name = `${prefix}${HIDDEN_ADDRESS_FIELD}`;
  hiddenInput.id = `${prefix}${HIDDEN_ADDRESS_FIELD}`;
  hiddenInput.value = JSON.stringify({});

  // Add it to the form
  let form;

  if (isClassicCheckout()) {
    form = document.querySelector('form.woocommerce-checkout');
  } else {
    form = document.querySelector('form.wc-block-checkout__form');
  }

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
  const shipToDifferentAddressCheckbox = document.querySelector(
    '[name="ship_to_different_address"]',
  ) as HTMLInputElement;

  // Check which widget was changed
  if (event.detail.appIdentifier === BILLING_ID) {
    writeAddressToFields(getClassicCheckoutConfig().prefixBilling, address);

    if (!shipToDifferentAddressCheckbox?.checked) {
      writeAddressToFields(getClassicCheckoutConfig().prefixShipping, address);
    }
  } else if (event.detail.appIdentifier === SHIPPING_ID) {
    writeAddressToFields(getClassicCheckoutConfig().prefixShipping, address);
  }
};

/**
 * Merges street/number/suffix into an internation "address"-style field.
 * @param address
 */
const mergeAddressFields = (address: AddressEventPayload['detail']): string[] | null => {
  const merged = [address.street, address.houseNumber, address.houseNumberSuffix].filter(
    (val): val is string => !!val?.length && val.length > 0,
  );

  if (merged.length) {
    return merged;
  }

  return null;
};

/**
 * Writes the whole address to either the billing/shipping hidden input for integral store in the DB.
 * @param prefix
 * @param address
 */
const addressToHiddenInput = (prefix: string, address: AddressEventPayload['detail']) => {
  const HIDDEN_ADDRESS_FIELD = useSettings().checkoutAddressHiddenInputName;
  const hiddenInput = document.querySelector(`#${prefix}${HIDDEN_ADDRESS_FIELD}`) as HTMLInputElement;
  hiddenInput.value = JSON.stringify(address);
};

/**
 * Writes a MyPa address object to individual wooc fields
 * @param prefix
 * @param address
 */
const writeAddressToFields = (prefix: string, address: AddressEventPayload['detail']) => {
  addressToHiddenInput(prefix, address);
  const address1Values = mergeAddressFields(address);

  // eslint-disable-next-line complexity
  ALL_ADDRESS_FIELDS.forEach((field) => {
    const fieldId = `${prefix}${field}`;
    const woocFieldInput = document.querySelector(`#${fieldId} input`);

    if (!woocFieldInput) {
      // eslint-disable-next-line no-console
      console.debug(`Could not find field ${fieldId}`);
      return;
    }

    switch (field) {
      case 'address_1_field':
        (woocFieldInput as HTMLInputElement).value = address1Values?.join(' ') ?? '';
        break;
      case 'house_number_field':
        (woocFieldInput as HTMLInputElement).value = address.houseNumber;
        break;
      case 'house_number_suffix_field':
        (woocFieldInput as HTMLInputElement).value = address.houseNumberSuffix ?? '';
        break;
      case 'street_field':
      case 'street_name_field':
        (woocFieldInput as HTMLInputElement).value = address.street;
        break;
      case 'city_field':
        (woocFieldInput as HTMLInputElement).value = address.city;
        break;
      case 'postcode_field':
        (woocFieldInput as HTMLInputElement).value = address.postalCode;
        break;
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

export const wrapperToAppIdentifier = (wrapper?: unknown[]): string => {
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
