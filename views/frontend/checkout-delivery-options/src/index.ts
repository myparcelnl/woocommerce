import './assets/scss/index.scss';
import {
  EVENT_UPDATED_ADDRESS,
  EVENT_UPDATED_DELIVERY_OPTIONS,
  EVENT_UPDATE_DELIVERY_OPTIONS,
  EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED,
  EVENT_WOOCOMMERCE_UPDATED_CHECKOUT,
  FIELD_SHIP_TO_DIFFERENT_ADDRESS,
} from './data';
import {onDeliveryOptionsAddressUpdate, onDeliveryOptionsUpdate} from './listeners';
import {AddressType} from './types';
import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';
import {addAddressListeners} from './addAddressListeners';
import {injectHiddenInput} from './injectHiddenInput';
import {synchronizeAddress} from './synchronizeAddress';
import {triggerEvent} from './triggerEvent';
import {updateAddress} from './updateAddress';
import {updateShippingMethod} from './updateShippingMethod';
import {useDeliveryOptionsStore} from './store/useDeliveryOptionsStore';
import {initializeStores} from './init/initializeStores';

export interface FrontendAppContext {
  deliveryOptions: {
    config: MyParcelDeliveryOptions.Config;
    strings: MyParcelDeliveryOptions.Strings;
    settings: FrontendSettings;
  };
}

export type FrontendSettings = {
  addressType: AddressType | null;
  ajaxHookGetConfig: string;
  ajaxUrl: string;
  allowedShippingMethods: string[];
  alwaysShow: boolean;
  disallowedShippingMethods: string[];
  hasDeliveryOptions: boolean;
  hasSplitAddressFields: boolean;
  hiddenInputName: string;
  shippingMethod: string | null;
  splitAddressFieldsCountries: string[];
};

(() => {
  [
    'myparcel_update_config',
    'myparcel_update_delivery_options',
    'myparcel_updated_address',
    'myparcel_updated_delivery_options',
    'myparcel_disable_delivery_options',
    'myparcel_show_delivery_options',
    'myparcel_hide_delivery_options',
    'myparcel_render_delivery_options',
  ].forEach((event) => {
    document.addEventListener(event, (e) => {
      console.log(event, e.detail);
    });
  });

  initializeStores();

  injectHiddenInput();
  addAddressListeners();

  const addressCheckbox = jQuery(FIELD_SHIP_TO_DIFFERENT_ADDRESS);

  // Handle switching between shipping and billing address.
  if (addressCheckbox.val()) {
    addressCheckbox.on('change', addAddressListeners);
  }

  document.addEventListener(EVENT_UPDATED_ADDRESS, onDeliveryOptionsAddressUpdate);
  document.addEventListener(EVENT_UPDATED_DELIVERY_OPTIONS, onDeliveryOptionsUpdate);

  /*
   * jQuery events.
   */
  jQuery(document.body).on(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, synchronizeAddress);
  jQuery(document.body).on(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, updateAddress);
  jQuery(document.body).on(EVENT_WOOCOMMERCE_UPDATED_CHECKOUT, updateShippingMethod);

  const deliveryOptions = useDeliveryOptionsStore();

  triggerEvent(EVENT_UPDATE_DELIVERY_OPTIONS, deliveryOptions.state);

  updateShippingMethod();
})();
