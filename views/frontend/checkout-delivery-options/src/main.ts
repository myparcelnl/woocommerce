import '../assets/scss/index.scss';
import {
  EVENT_HIDE_DELIVERY_OPTIONS,
  EVENT_SHOW_DELIVERY_OPTIONS,
  EVENT_UPDATED_ADDRESS,
  EVENT_UPDATED_DELIVERY_OPTIONS,
  EVENT_UPDATE_CONFIG,
  EVENT_UPDATE_DELIVERY_OPTIONS,
} from '@myparcel-pdk/checkout/src';
import {getFrontendContext, initializeCommonStores, triggerEvent} from '@myparcel-woocommerce/frontend-common';
import {injectHiddenInput, updateCheckoutForm} from './helpers';
import {initializeDeliveryOptionsStore} from './init';
import {onDeliveryOptionsUpdate} from './listeners';
import {useDeliveryOptionsStore} from './store';

[
  EVENT_UPDATE_DELIVERY_OPTIONS,
  EVENT_UPDATED_DELIVERY_OPTIONS,
  EVENT_UPDATED_ADDRESS,
  EVENT_UPDATE_CONFIG,
  EVENT_SHOW_DELIVERY_OPTIONS,
  EVENT_HIDE_DELIVERY_OPTIONS,
].forEach((event) => {
  document.addEventListener(event, (e) => {
    console.warn(event, e.detail);
  });
});

jQuery(() => {
  console.log('[START]');
  const context = getFrontendContext();

  initializeCommonStores(context);
  initializeDeliveryOptionsStore(context);

  injectHiddenInput();

  // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
  const form = document.querySelector<HTMLFormElement>('form[name="checkout"]')!;

  updateCheckoutForm({currentTarget: form} as unknown as Event);

  form?.addEventListener('change', updateCheckoutForm);

  document.addEventListener(EVENT_UPDATED_DELIVERY_OPTIONS, onDeliveryOptionsUpdate);

  const deliveryOptions = useDeliveryOptionsStore();

  triggerEvent(EVENT_UPDATE_DELIVERY_OPTIONS, deliveryOptions.state);
  console.log('[END]');
});
