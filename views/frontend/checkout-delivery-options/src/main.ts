import '../assets/scss/index.scss';
import {defaultGetPackageType} from '@myparcel-pdk/checkout-delivery-options';
import {
  PdkDeliveryOptionsEvent,
  initializeCheckoutDeliveryOptions as initialize,
  useEvent,
  usePdkCheckout,
  updateContext,
  useCheckoutStore,
  getPackageTypeFromShippingMethod,
  StoreListener,
} from '@myparcel-pdk/checkout';
import {getHighestShippingClass} from './utils';

const initializeCheckoutDeliveryOptions = () => {
  const checkout = useCheckoutStore();

  checkout.on(StoreListener.Update, async (newState, oldState) => {
    if (newState.form.shippingMethod === oldState?.form?.shippingMethod) {
      return;
    }

    const context = await updateContext();

    console.log('updated context:', context.settings.highestShippingClass);
    console.log('getHighestShippingClass', getHighestShippingClass());
  });

  initialize({
    getPackageType() {
      const shippingClass = getHighestShippingClass();

      console.log('getPackageType', shippingClass, shippingClass && getPackageTypeFromShippingMethod(shippingClass));

      return shippingClass ? getPackageTypeFromShippingMethod(shippingClass) : defaultGetPackageType();
    },
  });

  document.addEventListener(useEvent(PdkDeliveryOptionsEvent.DeliveryOptionsUpdated), () => {
    jQuery(document.body).trigger('update_checkout');
  });
};

usePdkCheckout().onInitialize(initializeCheckoutDeliveryOptions);
