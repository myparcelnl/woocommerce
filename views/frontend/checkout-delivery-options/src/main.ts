import '../assets/scss/index.scss';
import {
  EVENT_HIDE_DELIVERY_OPTIONS,
  EVENT_SHOW_DELIVERY_OPTIONS,
  EVENT_UPDATED_ADDRESS,
  EVENT_UPDATED_DELIVERY_OPTIONS,
  EVENT_UPDATE_CONFIG,
  EVENT_UPDATE_DELIVERY_OPTIONS,
  EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED,
  EVENT_WOOCOMMERCE_UPDATED_CHECKOUT,
  FIELD_SHIP_TO_DIFFERENT_ADDRESS,
  getFrontendContext,
  initializeCommonStores,
  triggerEvent,
  useDeliveryOptionsStore,
} from '@myparcel-woocommerce/frontend-common';
import {addAddressListeners} from './addAddressListeners';
import {initializeDeliveryOptionsStore} from './init';
import {injectHiddenInput} from './injectHiddenInput';
import {onDeliveryOptionsUpdate} from './listeners';
import {updateAddress} from './updateAddress';
import {updateShippingMethod} from './updateShippingMethod';

[
  EVENT_UPDATE_DELIVERY_OPTIONS,
  EVENT_UPDATED_DELIVERY_OPTIONS,
  EVENT_UPDATED_ADDRESS,
  EVENT_UPDATE_CONFIG,
  EVENT_SHOW_DELIVERY_OPTIONS,
  EVENT_HIDE_DELIVERY_OPTIONS,
].forEach((event) => {
  document.addEventListener(event, (e) => {
    console.log(event, e.detail);
  });
});

jQuery(() => {
  const addressCheckbox = jQuery(FIELD_SHIP_TO_DIFFERENT_ADDRESS);

  // Handle switching between shipping and billing address.
  addressCheckbox?.on('change', addAddressListeners);

  document.addEventListener(EVENT_UPDATED_DELIVERY_OPTIONS, onDeliveryOptionsUpdate);

  /*
   * jQuery events.
   */
  jQuery(document.body).on(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, updateAddress);
  jQuery(document.body).on(EVENT_WOOCOMMERCE_UPDATED_CHECKOUT, updateShippingMethod);

  const context = getFrontendContext();

  initializeCommonStores(context);
  initializeDeliveryOptionsStore(context);

  injectHiddenInput();
  addAddressListeners();

  const deliveryOptions = useDeliveryOptionsStore();

  triggerEvent(EVENT_UPDATE_DELIVERY_OPTIONS, deliveryOptions.state);

  updateShippingMethod();
});
