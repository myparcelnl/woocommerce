import {AddressField, AddressType, PdkField, type PdkFormData} from '@myparcel-dev/pdk-checkout-common';
import {PdkUtil, useUtil, updateContext, SeparateAddressField} from '@myparcel-dev/pdk-checkout';
import {type CheckoutConfig} from '../../types';
import {useWcCartStore} from './useWcCartStore';
import {getShippingRate} from './getShippingRate';

const MYPARCEL_BLOCK_FIELDS_PREFIX = 'myparcelnl/';

// eslint-disable-next-line max-lines-per-function
export const getBlocksCheckoutConfig = (): CheckoutConfig => {
  const addressFields = {
    eoriNumber: `eori_number`,
    vatNumber: `vat_number`,
    [AddressField.Address1]: `address_1`,
    [AddressField.Address2]: `address_2`,
    [AddressField.City]: `city`,
    [AddressField.Country]: `country`,
    [AddressField.PostalCode]: `postcode`,
    [SeparateAddressField.Street]: `${MYPARCEL_BLOCK_FIELDS_PREFIX}street_name`,
    [SeparateAddressField.Number]: `${MYPARCEL_BLOCK_FIELDS_PREFIX}house_number`,
    [SeparateAddressField.NumberSuffix]: `${MYPARCEL_BLOCK_FIELDS_PREFIX}house_number_suffix`,
  };

  const hasAddressType = (addressType: AddressType): boolean => {
    const billingElement = document.querySelector('#billing-fields');

    return AddressType.Shipping === addressType || billingElement !== null;
  };

  return {
    addressFields,
    fieldAddressType: 'checkbox-control-0',
    fieldShippingMethod: 'shipping_method',
    prefixBilling: 'billing-',
    prefixShipping: 'shipping-',

    shippingMethodFormDataKey: PdkField.ShippingMethod,
    addressTypeFormDataKey: PdkField.AddressType,

    config: {
      /**
       * Update whenever the shipping method or the address changes.
       */
      formChange(callback) {
        const wcCartStore = useWcCartStore();
        let previousShippingRate = getShippingRate();
        let previousCustomerData = JSON.stringify(wcCartStore.selectors.getCustomerData());

        wp.data.subscribe(async () => {
          const currentShippingRate = getShippingRate();
          const currentCustomerData = wcCartStore.selectors.getCustomerData();

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
        const wcCartStore = useWcCartStore();
        const customerData = wcCartStore.selectors.getCustomerData();
        const formData: PdkFormData = {};

        [AddressType.Shipping, AddressType.Billing].forEach((addressType) => {
          Object.keys(addressFields).forEach((field) => {
            formData[`${addressType}-${addressFields[field]}`] =
              customerData[`${addressType}Address`][addressFields[field]];
          });
        });

        const shippingRates = wcCartStore.selectors.getShippingRates();
        const selectedRate = shippingRates[0]?.shipping_rates.find((rate) => rate.selected);

        formData[PdkField.ShippingMethod] = selectedRate?.rate_id;

        // In the blocks checkout, the shipping address is *always* shown and billing address is optional. MyParcel uses the shipping address only.
        formData[PdkField.AddressType] = AddressType.Shipping;
        return formData;
      },

      getAddressType() {
        // Always use shipping in the blocks checkout.
        return AddressType.Shipping;
      },

      getForm() {
        const getElement = useUtil(PdkUtil.GetElement);

        // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
        return getElement('.wc-block-checkout__form')!;
      },

      hasAddressType,

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
