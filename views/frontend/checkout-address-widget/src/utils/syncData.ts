import {
  CONFIGURATION_UPDATE_EVENT,
  type AddressEventPayload,
  type Alpha2CountryCode,
  type ConfigEventPayload,
  type ConfigObject,
} from 'mypa-address-widget';
import {getClassicCheckoutConfig} from '@myparcel-woocommerce/frontend-common';
import {AddressType, splitFullStreet, useCheckoutStore, useSettings} from '@myparcel-pdk/checkout';
import {ALL_ADDRESS_FIELDS} from '../constants/fields';
import {hideAddressFields, showAddressFields} from './showHide';
import {BILLING_ID, getConfig, SHIPPING_ID} from './init';

export const createHiddenInput = (prefix: string): HTMLInputElement => {
  const HIDDEN_ADDRESS_FIELD = useSettings().checkoutAddressHiddenInputName;
  const hiddenInput = document.createElement('input');
  hiddenInput.type = 'hidden';
  hiddenInput.name = `${prefix}${HIDDEN_ADDRESS_FIELD}`;
  hiddenInput.id = `${prefix}${HIDDEN_ADDRESS_FIELD}`;
  hiddenInput.value = '';

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
  } else {
    // eslint-disable-next-line no-console
    console.warn(`Unknown appIdentifier ${event.detail.appIdentifier}`);
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
  const addressCopy = {...address};
  delete addressCopy.appIdentifier;

  // Empty value instead of empty object if the address object is empty
  if (Object.keys(addressCopy).length === 0) {
    hiddenInput.value = '';
  } else {
    hiddenInput.value = JSON.stringify(addressCopy);
  }

  hiddenInput.dispatchEvent(new Event('change', {bubbles: true}));
};

/**
 * Writes a MyPa address object to individual wooc fields
 * @param prefix
 * @param address
 */
export const writeAddressToFields = (prefix: string, address: AddressEventPayload['detail']): void => {
  addressToHiddenInput(prefix, address);

  const FIELD_VALUE_MAP: Record<string, (address: AddressEventPayload['detail']) => string> = {
    /* eslint-disable @typescript-eslint/naming-convention */
    address_1_field: (address) => {
      const mergedFields = mergeAddressFields(address);
      return mergedFields?.length ? mergedFields.join(' ') : '';
    },
    house_number_field: (address) => address.houseNumber ?? '',
    house_number_suffix_field: (address) => address.houseNumberSuffix ?? '',
    street_field: (address) => address.street ?? '',
    street_name_field: (address) => address.street ?? '',
    city_field: (address) => address.city ?? '',
    postcode_field: (address) => address.postalCode ?? '',
    /* eslint-enable @typescript-eslint/naming-convention */
  };

  // eslint-disable-next-line complexity
  ALL_ADDRESS_FIELDS.forEach((field) => {
    const fieldId = `${prefix}${field}`;
    const woocFieldInput = document.querySelector(`#${fieldId} input`) as HTMLInputElement;

    if (!woocFieldInput) {
      // eslint-disable-next-line no-console
      console.debug(`Could not find field ${fieldId}`);
      return;
    }

    const getMappedValue = FIELD_VALUE_MAP[field];

    if (getMappedValue) {
      woocFieldInput.value = getMappedValue(address);
      woocFieldInput.dispatchEvent(new Event('change', {bubbles: true}));
    }
  });
};

/**
 * Map the address in the  checkout store to the address widget address.
 * Mostly used for the initial load of the address widget to set a prefilled address.
 */
export const getAddressFromPdkStore = (appIdentifier: string): ConfigObject['address'] => {
  let addressType = AddressType.Billing;

  if (appIdentifier === SHIPPING_ID) {
    addressType = AddressType.Shipping;
  }

  const checkoutStore = useCheckoutStore();
  const formData = checkoutStore.state.form[addressType];
  const splitAddress = splitFullStreet(formData.address1);

  return {
    countryCode: getSelectedCountry(),
    street: splitAddress.street.length ? splitAddress.street : undefined,
    houseNumber: splitAddress.number.length ? splitAddress.number : undefined,
    houseNumberSuffix: splitAddress.numberSuffix.length ? splitAddress.numberSuffix : undefined,
    city: formData.city.length ? formData.city : undefined,
    postalCode: formData.postalCode.length ? formData.postalCode : undefined,
  };
};

/**
 * Helper function to determine the currently selected country in WooCommerce
 * @returns
 */
export const getSelectedCountry = (): Alpha2CountryCode => {
  const element = document.querySelector('select.country_to_state, input.country_to_state');
  return (element as HTMLSelectElement)?.value as Alpha2CountryCode;
};

export const wrapperToAppIdentifier = (wrapper?: unknown[]): string => {
  // Interpret wrapper as DOM element
  const wrapperElement = wrapper?.[0] as HTMLElement;
  const wrapperClass = wrapperElement?.className;
  return wrapperClass === 'woocommerce-shipping-fields' ? SHIPPING_ID : BILLING_ID;
};

const updateWidgetCountry = (country: string, appIdentifier: string) => {
  const countryCode = country.toUpperCase() as Alpha2CountryCode;
  const address = getAddressFromPdkStore(appIdentifier);
  address.countryCode = countryCode;

  document.dispatchEvent(
    new CustomEvent<ConfigEventPayload>(CONFIGURATION_UPDATE_EVENT, {
      detail: {appIdentifier, config: {address}},
    }),
  );
};
