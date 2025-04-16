import {createApp} from 'vue';
import {
  default as TheAddressWidget,
  ADDRESS_SELECTED_EVENT,
  type ConfigObject,
  type AddressEventPayload,
} from 'mypa-address-widget';
import {
  EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED,
  getBlocksCheckoutConfig,
  getClassicCheckoutConfig,
  isClassicCheckout,
} from '@myparcel-woocommerce/frontend-common';
import {handleCountryChange, syncAddressWhenSelected, getSelectedCountry, createHiddenInput} from './syncData';
import {hideAddressFields} from './showHide';

/**
 * The element IDs to mount the widget on
 */
export const SHIPPING_ID = 'shipping_address_widget';

export const BILLING_ID = 'billing_address_widget';

export const getConfig = (appIdentifier: string): ConfigObject => {
  return {
    country: getSelectedCountry(),
    apiUrl: 'https://address.api.myparcel.nl', // TODO: replace with the proxy URL
    apiKey: window.TemporaryMyParcelAddressConfig.apiKey as string, // @TODO remove
    appIdentifier,
    classNames: {
      fieldWrapper: ['form-row form-row-wide'],
    },
  };
};

export const initializeListeners = (): void => {
  // Listen for changes to the address from the widget.
  document.addEventListener(ADDRESS_SELECTED_EVENT, (event: Event) => {
    if (!(event instanceof CustomEvent)) {
      // eslint-disable-next-line no-console
      console.warn('Event is not a CustomEvent');
      return;
    }

    syncAddressWhenSelected(event as AddressEventPayload);
  });

  // Listen for changes to the selected country in woocommerce (can be either shipment or billing)
  jQuery(document.body).on(
    // @ts-expect-error this is a valid event
    EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED,
    (event: Event, country: string, $wrapper: unknown[]) => {
      handleCountryChange(event, country, $wrapper);
    },
  );
};

/**
 * Callback to initialize the widget, called by the PDK
 * @returns void
 */
export const initializeAddressWidget = (): void => {
  if (!window) {
    return;
  }

  // Conditionally hide the address fields
  const country = getSelectedCountry();

  if (country === 'NL') {
    hideAddressFields();
  }

  const config = isClassicCheckout() ? getClassicCheckoutConfig() : getBlocksCheckoutConfig();

  // Mount on billing
  createHiddenInput(config.prefixBilling);

  createApp(TheAddressWidget, {
    config: getConfig(BILLING_ID),
  }).mount(`#${BILLING_ID}`);

  // Mount on shipping
  createHiddenInput(config.prefixShipping);

  createApp(TheAddressWidget, {
    config: getConfig(SHIPPING_ID),
  }).mount(`#${SHIPPING_ID}`);

  initializeListeners();
};
