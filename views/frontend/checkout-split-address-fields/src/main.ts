import '../assets/scss/index.scss';
import {setAddress, synchronizeAddress} from './utils';
import {EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED} from '@myparcel-woocommerce/frontend-common/src';

jQuery(() => {
  // @ts-expect-error this is a string.
  jQuery(document.body).on(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, synchronizeAddress);

  const billingStreet = document.querySelector('#billing_street_name');
  const shippingStreet = document.querySelector('#shipping_street_name');

  /**
   * Trigger hiding of address line 1 and 2 if necessary. The timeout is necessary, otherwise the order summary is going
   * to flash.
   *
   * It checks for MyParcel_Frontend before running as that script has the same code.
   */
  // if (!window.hasOwnProperty('MyParcel_Frontend')) {
  //   setTimeout(() => {
  //     const event = new Event('change');
  //
  //     document.querySelectorAll('.country_to_state').forEach((selector) => {
  //       selector.dispatchEvent(event);
  //     });
  //   }, 100);
  // }

  /**
   * Set the correct autocomplete attribute on the street fields if none is present.
   */
  [billingStreet, shippingStreet].forEach((element) => {
    if (!element) {
      return;
    }

    if (!element.getAttribute('autocomplete')) {
      element.setAttribute('autocomplete', 'street-address');
    }

    element.addEventListener('load', setAddress);
    element.addEventListener('animationend', setAddress);
  });
});
