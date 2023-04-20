import '../assets/scss/index.scss';
import {
  PdkDeliveryOptionsEvent,
  initializeCheckoutDeliveryOptions,
  useEvent,
  usePdkCheckout,
} from '@myparcel-pdk/checkout/src';

usePdkCheckout().onInitialize(() => {
  initializeCheckoutDeliveryOptions();

  document.addEventListener(useEvent(PdkDeliveryOptionsEvent.DeliveryOptionsUpdated), (event) => {
    console.log('deliveryOptionsUpdated', event);
    // $(document.body).trigger('update_checkout');
  });
});
