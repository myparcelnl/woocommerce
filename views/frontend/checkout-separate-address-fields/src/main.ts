import '../assets/scss/index.scss';
import {
  EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED,
  isClassicCheckout,
  useWcCartStore,
  useWcCheckoutStore,
} from '@myparcel-woocommerce/frontend-common';
import {AddressType, PdkEvent, StoreListener, useCheckoutStore} from '@myparcel-pdk/checkout-common';
import {
  initializeCheckoutSeparateAddressFields as initialize,
  SeparateAddressField,
  useEvent,
  usePdkCheckout,
} from '@myparcel-pdk/checkout';

const initializeCheckoutSeparateAddressFields = () => {
  // @ts-expect-error this is a valid event
  jQuery(document.body).on(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, (event: Event, newCountry: string) => {
    document.dispatchEvent(new CustomEvent(useEvent(PdkEvent.CheckoutUpdate), {detail: newCountry}));
  });

  initialize();

  if (!isClassicCheckout()) {
    const checkoutStore = useCheckoutStore();

    // We need to inform the PDK that billing fields are now present and should be listened to.
    const wcCheckoutStore = useWcCheckoutStore();
    let useShippingAsBilling = !!wcCheckoutStore.selectors.getUseShippingAsBilling();
    wp.data.subscribe(async () => {
      const newUseShippingAsBilling = wcCheckoutStore.selectors.getUseShippingAsBilling();

      if (useShippingAsBilling !== newUseShippingAsBilling) {
        useShippingAsBilling = newUseShippingAsBilling;

        await checkoutStore.set({
          addressTypes: useShippingAsBilling ? [AddressType.Shipping] : [AddressType.Shipping, AddressType.Billing],
        });
      }
    });

    // Set the address fields in the WC cart store when the MyParcel checkout data is updated.
    const wcCartStore = useWcCartStore();
    checkoutStore.on(StoreListener.Update, async (event) => {
      await wcCartStore.actions.setShippingAddress({
        address_1: [
          event.form.shipping[SeparateAddressField.Street],
          event.form.shipping[SeparateAddressField.Number],
          event.form.shipping[SeparateAddressField.NumberSuffix],
        ]
          .filter(Boolean)
          .join(' '),
      });
      await wcCartStore.actions.setBillingAddress({
        address_1: [
          event.form.billing[SeparateAddressField.Street],
          event.form.billing[SeparateAddressField.Number],
          event.form.billing[SeparateAddressField.NumberSuffix],
        ]
          .filter(Boolean)
          .join(' '),
      });
    });
  }
};

usePdkCheckout().onInitialize(initializeCheckoutSeparateAddressFields);
