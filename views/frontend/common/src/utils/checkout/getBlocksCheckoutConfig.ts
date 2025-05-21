import {AddressType, PdkField} from '@myparcel-pdk/checkout-common';
import {PdkUtil, useUtil, type PdkFormData, updateContext} from '@myparcel-pdk/checkout';
import {type CheckoutConfig} from '../../types';
import {useCartStore} from './useCartStore';
import {getShippingRate} from './getShippingRate';

// eslint-disable-next-line max-lines-per-function
export const getBlocksCheckoutConfig = (): CheckoutConfig => {
  const addressFields = {
    address1: `address_1`,
    address2: `address_2`,
    city: `city`,
    country: `country`,
    eoriNumber: `eori_number`,
    number: `house_number`,
    numberSuffix: `house_number_suffix`,
    postalCode: `postcode`,
    street: `street`,
    vatNumber: `vat_number`,
  };

  return {
    addressFields,
    fieldShippingMethod: 'shipping_method',
    prefixBilling: 'billing-',
    prefixShipping: 'shipping-',
    shippingMethodFormField: 'shippingMethod',

    config: {
      /**
       *
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
          Object.keys(addressFields).forEach((field) => {
            formData[`${addressType}-${addressFields[field]}`] = customerData.shippingAddress[addressFields[field]];
          });
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
