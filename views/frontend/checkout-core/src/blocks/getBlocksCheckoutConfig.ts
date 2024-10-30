import {
  FIELD_PREFIX_BILLING,
  FIELD_PREFIX_SHIPPING,
  FIELD_SHIPPING_METHOD,
} from '@myparcel-woocommerce/frontend-common';
import {AddressType, PdkField} from '@myparcel-pdk/checkout-common';
import {PdkUtil, useUtil, type PdkFormData, updateContext, AddressField} from '@myparcel-pdk/checkout';
import {type WcCartStore} from '../utils/useCartStore';
import {useCartStore, getShippingRate, createId, createFields, splitStreet} from '../utils';
import {type WooCommerceCheckoutConfig} from '../types';
import {ADDRESS_FIELDS_BLOCKS_CHECKOUT} from './constants';

const createSelector = (val: string, prefix: string) => {
  const matches = /(\w+)\/(\w+)$/.exec(val);

  if (!matches) {
    return createId(prefix + val);
  }

  return createId(`${prefix}${matches[1]}-${matches[1]}-${matches[2]}`);
};

// eslint-disable-next-line max-lines-per-function,@typescript-eslint/explicit-module-boundary-types
export const getBlocksCheckoutConfig = () => {
  return {
    addressFields: ADDRESS_FIELDS_BLOCKS_CHECKOUT,
    fieldShippingMethod: FIELD_SHIPPING_METHOD,
    prefixBilling: `${FIELD_PREFIX_BILLING}-`,
    prefixShipping: `${FIELD_PREFIX_SHIPPING}-`,
    shippingMethodFormField: 'shippingMethod',

    config: {
      formData: {
        [PdkField.AddressType]: 'ship_to_different_address',
        [PdkField.ShippingMethod]: `${FIELD_SHIPPING_METHOD}[0]`,
        [AddressType.Billing]: createFields(ADDRESS_FIELDS_BLOCKS_CHECKOUT, (val) => `${FIELD_PREFIX_BILLING}-${val}`),
        [AddressType.Shipping]: createFields(
          ADDRESS_FIELDS_BLOCKS_CHECKOUT,
          (val) => `${FIELD_PREFIX_SHIPPING}-${val}`,
        ),
      },

      fields: {
        [PdkField.AddressType]: createId('checkbox-control-0'),
        [PdkField.ShippingMethod]: createId(FIELD_SHIPPING_METHOD),
        [AddressType.Billing]: createFields(ADDRESS_FIELDS_BLOCKS_CHECKOUT, (val) =>
          createSelector(val, `${FIELD_PREFIX_BILLING}-`),
        ),
        [AddressType.Shipping]: createFields(ADDRESS_FIELDS_BLOCKS_CHECKOUT, (val) =>
          createSelector(val, `${FIELD_PREFIX_SHIPPING}-`),
        ),
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
        const customerData = cartStore.getCustomerData();
        const formData: PdkFormData = {};

        const updateAddress = (addressType: AddressType, newAddress: Record<string, string>) => {
          const storeMethod: keyof WcCartStore['dispatch'] =
            addressType === AddressType.Billing ? 'setBillingAddress' : 'setShippingAddress';

          const mappedAddress = Object.fromEntries(
            Object.entries(newAddress).reduce((acc, [key, value]) => {
              const resolvedKey = ADDRESS_FIELDS_BLOCKS_CHECKOUT[key as keyof typeof ADDRESS_FIELDS_BLOCKS_CHECKOUT];

              acc.push([resolvedKey, value]);

              return acc;
            }, [] as [string, string][]),
          );

          Object.entries(mappedAddress).forEach(([key, value]) => {
            formData[`${addressType}-${key}`] = value;
          });

          cartStore.dispatch[storeMethod](mappedAddress);
        };

        [AddressType.Shipping, AddressType.Billing].forEach((addressType) => {
          const newAddress: Record<string, string> = {};

          const addressObject =
            addressType === AddressType.Billing ? customerData.billingAddress : customerData.shippingAddress;

          Object.keys(ADDRESS_FIELDS_BLOCKS_CHECKOUT).forEach((field) => {
            const key = ADDRESS_FIELDS_BLOCKS_CHECKOUT[field as keyof typeof ADDRESS_FIELDS_BLOCKS_CHECKOUT];

            newAddress[field] = addressObject[key];
          });

          if (addressObject.street && !addressObject.number && !addressObject.numberSuffix) {
            console.log('street defined, number and numberSuffix not defined');
            const {street, number, numberSuffix} = splitStreet(addressObject.street);

            addressObject.street = street;
            addressObject.number = number;
            addressObject.numberSuffix = numberSuffix;
          }

          // Combine street, number and number suffix back into address1
          const newFullStreet = [addressObject.street, addressObject.number, addressObject.numberSuffix]
            .filter(Boolean)
            .join(' ')
            .trim();

          const currentFullStreet = (formData[`${addressType}-${AddressField.Address1}`] ?? '') as string;

          // Split the full street into street, number and number suffix if needed
          if (!newFullStreet && currentFullStreet) {
            console.log('new street not defined, current street defined');
            Object.assign(newAddress, splitStreet(currentFullStreet));
          }

          // Save the full street back to the store if it doesn't match
          if (currentFullStreet !== newFullStreet) {
            console.log('current street does not match new street');
            newAddress[AddressField.Address1] = newFullStreet;
          }

          updateAddress(addressType, newAddress);
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
  } satisfies WooCommerceCheckoutConfig;
};
