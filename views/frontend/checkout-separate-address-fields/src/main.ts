import '../assets/scss/index.scss';
import {
  EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED,
  isClassicCheckout,
  useWcCartStore,
  useWcCheckoutStore,
} from '@myparcel-woocommerce/frontend-common';
import {
  AddressField,
  AddressType,
  PdkEvent,
  StoreListener,
  useCheckoutStore,
  useSettings,
  type AddressFields,
} from '@myparcel-pdk/checkout-common';
import {
  initializeCheckoutSeparateAddressFields as initialize,
  SeparateAddressField,
  useEvent,
  usePdkCheckout,
  type SeparateAddressFields,
} from '@myparcel-pdk/checkout';

// eslint-disable-next-line max-lines-per-function
const initializeCheckoutSeparateAddressFields = async () => {
  // @ts-expect-error this is a valid event
  jQuery(document.body).on(EVENT_WOOCOMMERCE_COUNTRY_TO_STATE_CHANGED, (event: Event, newCountry: string) => {
    document.dispatchEvent(new CustomEvent(useEvent(PdkEvent.CheckoutUpdate), {detail: newCountry}));
  });

  initialize();

  if (!isClassicCheckout()) {
    const checkoutStore = useCheckoutStore();

    /**
     * Let the PDK Checkout know which address types are available.
     * If the "Use shipping address as billing address" option is enabled, only the shipping address type is available.
     * If it is disabled, both shipping and billing address types are available
     */
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

    /*
     * Listen to changes in the PDK checkout store and update the shipping and billing address fields in Woocommerce
     * if the country is in the list of countries with separate address fields.
     */
    const wcCartStore = useWcCartStore();
    const settings = useSettings();
    const syncSeparateFieldsToAddress1 = async (
      addressType: AddressType,
      data: AddressFields & SeparateAddressFields,
    ): Promise<void> => {
      if (!settings.countriesWithSeparateAddressFields.includes(data[AddressField.Country])) {
        return;
      }

      const address1 = [
        data[SeparateAddressField.Street],
        data[SeparateAddressField.Number],
        data[SeparateAddressField.NumberSuffix],
      ]
        .filter(Boolean)
        .join(' ');

      if (addressType === AddressType.Shipping) {
        await wcCartStore.actions.setShippingAddress({address_1: address1});
      } else {
        await wcCartStore.actions.setBillingAddress({address_1: address1});
      }
    };

    // There is a race condition on page load - where either the store already contains separate address field data and will not our store listeners
    // or the store is not yet fully initialized and the update will trigger anyway.
    // We need to handle both cases.
    await syncSeparateFieldsToAddress1(
      AddressType.Shipping,
      checkoutStore.state.form.shipping as AddressFields & SeparateAddressFields,
    );
    await syncSeparateFieldsToAddress1(
      AddressType.Billing,
      checkoutStore.state.form.billing as AddressFields & SeparateAddressFields,
    );

    checkoutStore.on(StoreListener.Update, async (event) => {
      await syncSeparateFieldsToAddress1(
        AddressType.Shipping,
        event.form.shipping as AddressFields & SeparateAddressFields,
      );
      await syncSeparateFieldsToAddress1(
        AddressType.Billing,
        event.form.billing as AddressFields & SeparateAddressFields,
      );
    });
  }
};

usePdkCheckout().onInitialize(initializeCheckoutSeparateAddressFields);
