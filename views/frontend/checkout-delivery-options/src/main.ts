import '../assets/scss/index.scss';
import {
  PdkDeliveryOptionsEvent,
  initializeCheckoutDeliveryOptions as initialize,
  useEvent,
  usePdkCheckout,
} from '@myparcel-pdk/checkout/src';

const initializeCheckoutDeliveryOptions = () => {
  void initialize();

  document.addEventListener(useEvent(PdkDeliveryOptionsEvent.DeliveryOptionsUpdated), () => {
    jQuery(document.body).trigger('update_checkout');
  });
};

usePdkCheckout().onInitialize(initializeCheckoutDeliveryOptions);
