import './assets/scss/index.scss';
import {
  EVENT_UPDATED_ADDRESS,
  EVENT_UPDATED_DELIVERY_OPTIONS,
  EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED,
  EVENT_WOOCOMMERCE_UPDATED_CHECKOUT,
  FIELD_SHIP_TO_DIFFERENT_ADDRESS,
} from './data';
import {onDeliveryOptionsAddressUpdate, onDeliveryOptionsUpdate} from './listeners';
import {addAddressListeners} from './addAddressListeners';
import {injectHiddenInput} from './injectHiddenInput';
import {synchronizeAddress} from './synchronizeAddress';
import {updateAddress} from './updateAddress';
import {updateShippingMethod} from './updateShippingMethod';
import {initializeStore} from './store';

(() => {
  //
  //   /**
  //  * Whether the delivery options are currently shown or not. Defaults to true and can be set to false depending on
  //  *  shipping methods.
  //  *
  //  * @type {Boolean}
  //  */
  // export const hasDeliveryOptions = true;
  //
  // /**
  //  * @type {RegExp}
  //  */
  // export const splitStreetRegex = /(.*?)\s?(\d{1,4})[/\s-]{0,2}([A-z]\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[A-z][A-z\s]{0,3})?$/;
  //
  // /**
  //  * @type {Boolean}
  //  */
  // export const isUsingSplitAddressFields = Boolean(Number(MyParcelDisplaySettings.isUsingSplitAddressFields));
  //
  // /**
  //  * @type {String[]}
  //  */
  // export const {splitAddressFieldsCountries} = MyParcelDisplaySettings;
  //
  // /**
  //  * @type {Array}
  //  */
  // export const allowedShippingMethods = JSON.parse(MyParcelDeliveryOptions.allowedShippingMethods);
  //
  // /**
  //  * @type {Array}
  //  */
  // export const disallowedShippingMethods = JSON.parse(MyParcelDeliveryOptions.disallowedShippingMethods);
  //
  // /**
  //  * @type {Boolean}
  //  */
  // export const alwaysShow = Boolean(parseInt(MyParcelDeliveryOptions.alwaysShow));
  //
  // /**
  //  * @type {Object<String, String>}
  //  */
  // export const previousCountry = {};
  //
  // /**
  //  * @type {?String}
  //  */
  // export const selectedShippingMethod = null;
  //
  // /**
  //  * @type {Element}
  //  */
  // export const hiddenDataInput = null;
  //
  // /**
  //  * @type {String}
  //  */
  // export const addressType = null;

  initializeStore();

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

  jQuery(document.body).on('myparcelnl-woocommerce:updateAddress', updateAddress);

  updateShippingMethod();
})();