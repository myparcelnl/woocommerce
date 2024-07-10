import '../assets/scss/index.scss';
import {
  PdkDeliveryOptionsEvent,
  initializeCheckoutDeliveryOptions as initialize,
  useEvent,
  usePdkCheckout,
  getPackageTypeFromShippingMethod,
  defaultGetPackageType,
} from '@myparcel-pdk/checkout';
import {getHighestShippingClass} from './utils';

const initializeCheckoutDeliveryOptions = () => {
  initialize({
    getPackageType() {
      const shippingClass = getHighestShippingClass();

      return shippingClass ? getPackageTypeFromShippingMethod(shippingClass) : defaultGetPackageType();
    },
  });

  document.addEventListener(useEvent(PdkDeliveryOptionsEvent.DeliveryOptionsUpdated), () => {
    jQuery(document.body).trigger('update_checkout');
  });
};

usePdkCheckout().onInitialize(initializeCheckoutDeliveryOptions);
