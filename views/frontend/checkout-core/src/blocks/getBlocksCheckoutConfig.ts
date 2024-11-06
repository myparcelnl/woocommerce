import {FIELD_PREFIX_BILLING, FIELD_PREFIX_SHIPPING} from '@myparcel-woocommerce/frontend-common';
import {AddressType, PdkField, type PdkCheckoutConfigInput, useConfig} from '@myparcel-pdk/checkout-common';
import {PdkUtil, useUtil, type PdkFormData, updateContext, useCheckoutStore} from '@myparcel-pdk/checkout';
import {useCartStore, getShippingRate, createFields} from '../utils';
import {SELECTOR_FORM_BLOCKS} from '../constants';
import {updateAddressInStore} from './updateAddressInStore';
import {resolveAddress} from './resolveAddress';
import {createBlocksCheckoutFieldSelectors} from './createBlocksCheckoutFieldSelectors';
import {ADDRESS_FIELDS_BLOCKS_CHECKOUT} from './constants';

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types,max-lines-per-function
export const getBlocksCheckoutConfig = () => {
  return {
    fields: {
      // Irrelevant because there's always a separate billing and shipping address object.
      [PdkField.AddressType]: '',
      // Value is manually resolved in getFormData.
      [PdkField.ShippingMethod]: '',
      [AddressType.Billing]: createBlocksCheckoutFieldSelectors(`${FIELD_PREFIX_BILLING}-`),
      [AddressType.Shipping]: createBlocksCheckoutFieldSelectors(`${FIELD_PREFIX_SHIPPING}-`),
    },

    formData: {
      [PdkField.AddressType]: PdkField.AddressType,
      [PdkField.ShippingMethod]: PdkField.ShippingMethod,
      [AddressType.Billing]: createFields(ADDRESS_FIELDS_BLOCKS_CHECKOUT, (val) => `${FIELD_PREFIX_BILLING}-${val}`),
      [AddressType.Shipping]: createFields(ADDRESS_FIELDS_BLOCKS_CHECKOUT, (val) => `${FIELD_PREFIX_SHIPPING}-${val}`),
    },

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

    // eslint-disable-next-line max-lines-per-function
    getFormData() {
      const cartStore = useCartStore();
      const {billingAddress, shippingAddress} = cartStore.getCustomerData();
      const formData: PdkFormData = {};

      [AddressType.Shipping, AddressType.Billing].forEach((addressType) => {
        const addressObject = addressType === AddressType.Billing ? billingAddress : shippingAddress;
        const newAddress = resolveAddress(addressObject);

        Object.entries(newAddress).forEach(([key, value]) => {
          formData[`${addressType}-${key}`] = value;
        });
      });

      const shippingRates = cartStore.getShippingRates();
      const selectedRate = shippingRates[0].shipping_rates.find((rate) => rate.selected);

      return {
        ...formData,
        [PdkField.AddressType]: AddressType.Shipping,
        [PdkField.ShippingMethod]: selectedRate?.rate_id,
      };
    },

    getForm() {
      const getElement = useUtil(PdkUtil.GetElement);

      // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
      return getElement<HTMLFormElement>(SELECTOR_FORM_BLOCKS)!;
    },

    hasAddressType() {
      return true;
    },

    initialize() {
      return new Promise((resolve) => {
        document.addEventListener('myparcel_wc_delivery_options_ready', () => {
          const config = useConfig();
          const form = config.getForm();

          form.addEventListener('change', (...args) => {
            const checkoutStore = useCheckoutStore();

            updateAddressInStore(AddressType.Shipping, resolveAddress(checkoutStore.state.form[AddressType.Shipping]));
            updateAddressInStore(AddressType.Billing, resolveAddress(checkoutStore.state.form[AddressType.Billing]));
          });

          resolve();
        });
      });
    },
  } satisfies Partial<PdkCheckoutConfigInput>;
};
