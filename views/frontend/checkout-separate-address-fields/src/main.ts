import '../assets/scss/index.scss';
import {EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED} from '@myparcel-woocommerce/frontend-common';
import {initializeCheckoutSeparateAddressFields as initialize, useEvent, usePdkCheckout} from '@myparcel-pdk/checkout';
import {PdkEvent} from '@myparcel-pdk/checkout-common';

const initializeCheckoutSeparateAddressFields = () => {
  // @ts-expect-error this is a valid event
  jQuery(document.body).on(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, (event: Event, newCountry: string) => {
    document.dispatchEvent(new CustomEvent(useEvent(PdkEvent.CheckoutUpdate), {detail: newCountry}));
  });

  initialize();
};

usePdkCheckout().onInitialize(initializeCheckoutSeparateAddressFields);
