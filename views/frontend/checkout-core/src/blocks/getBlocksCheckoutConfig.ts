import {AddressType, PdkField} from '@myparcel-pdk/checkout-common';
import {PdkUtil, useUtil, type PdkFormData, updateContext, AddressField} from '@myparcel-pdk/checkout';
import {type WcCartStore} from '../utils/useCartStore';
import {useCartStore, getShippingRate} from '../utils';
import {type CheckoutConfig} from '../types';

// eslint-disable-next-line max-lines-per-function
export const getBlocksCheckoutConfig = (): CheckoutConfig => {
  const addressFields = {
    address1: 'address_1',
    address2: 'address_2',
    city: 'city',
    country: 'country',
    eoriNumber: 'eori_number',
    postalCode: 'postcode',
    vatNumber: 'vat_number',

    /**
     * Our custom fields
     */
    street: 'myparcelnl/street_name',
    number: 'myparcelnl/house_number',
    numberSuffix: 'myparcelnl/house_number_suffix',
  };

  return {
    addressFields,
    fieldShippingMethod: 'shipping_method',
    prefixBilling: 'billing-',
    prefixShipping: 'shipping-',
    shippingMethodFormField: 'shippingMethod',

    config: {
      /**
       * Update whenever the shipping method or the address changes.
       */
      formChange(callback) {
        const cartStore = useCartStore();

        let previousShippingRate = getShippingRate();
        let previousCustomerData = JSON.stringify(cartStore.getCustomerData());

        wp.data.subscribe(async () => {
          const currentShippingRate = getShippingRate();
          const currentCustomerData = cartStore.getCustomerData();

          const shippingMethodChanged = previousShippingRate?.rate_id !== currentShippingRate?.rate_id;
          const customerDataChanged = previousCustomerData !== JSON.stringify(currentCustomerData);

          if (!shippingMethodChanged && !customerDataChanged) {
            return;
          }

          if (customerDataChanged) {
            previousCustomerData = JSON.stringify(currentCustomerData);
          }

          if (shippingMethodChanged) {
            previousShippingRate = currentShippingRate;

            await updateContext();
          }

          callback();
        });
      },

      getFormData() {
        const cartStore = useCartStore();
        const customerData = cartStore.getCustomerData();
        const formData: PdkFormData = {};

        [AddressType.Shipping, AddressType.Billing].forEach((addressType) => {
          const storeMethod: keyof WcCartStore['dispatch'] =
            addressType === AddressType.Billing ? 'setBillingAddress' : 'setShippingAddress';
          const addressObject =
            addressType === AddressType.Billing ? customerData.billingAddress : customerData.shippingAddress;

          Object.keys(addressFields).forEach((field) => {
            const key = addressFields[field as keyof typeof addressFields];

            formData[`${addressType}-${key}`] = addressObject[key];
          });

          // Combine street, number and number suffix back into address1
          const address1Key = addressFields[AddressField.Address1];
          const streetKey = addressFields.street;
          const numberKey = addressFields.number;
          const numberSuffixKey = addressFields.numberSuffix;

          const newFullStreet = [addressObject[streetKey], addressObject[numberKey], addressObject[numberSuffixKey]]
            .filter(Boolean)
            .join(' ')
            .trim();

          const currentFullStreet = formData[`${addressType}-${address1Key}`];

          console.log({
            currentFullStreet,
            newFullStreet,
            equal: currentFullStreet === newFullStreet,
          });

          // Save the full street back to the store if it doesn't match
          if (currentFullStreet !== newFullStreet) {
            console.log('dispatching');
            // TODO: het werkt bijna, alleen dit wordt nog infinite loop want address1 wordt steeds weer leeg gemaakt :(
            cartStore.dispatch[storeMethod]({[address1Key]: newFullStreet});
          }
        });

        const shippingRates = cartStore.getShippingRates();
        const selectedRate = shippingRates[0].shipping_rates.find((rate) => rate.selected);

        formData[PdkField.ShippingMethod] = selectedRate?.rate_id;

        return formData;
      },

      getForm() {
        const getElement = useUtil(PdkUtil.GetElement);

        // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
        return getElement('.wc-block-checkout__form')!;
      },

      hasAddressType() {
        return true;
      },

      initialize() {
        return new Promise((resolve) => {
          document.addEventListener('myparcel_wc_delivery_options_ready', () => {
            resolve();
          });
        });
      },
    },
  } satisfies CheckoutConfig;
};
