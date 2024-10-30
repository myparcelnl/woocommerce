import {
  FIELD_PREFIX_BILLING,
  FIELD_PREFIX_SHIPPING,
  FIELD_SHIPPING_METHOD,
} from '@myparcel-woocommerce/frontend-common';
import {AddressType, useConfig, PdkField} from '@myparcel-pdk/checkout-common';
import {useUtil, PdkUtil} from '@myparcel-pdk/checkout';
import {createId, createFields, createName} from '../utils';
import {type WooCommerceCheckoutConfig} from '../types';
import {ADDRESS_FIELDS_CLASSIC} from './constants';

// eslint-disable-next-line max-lines-per-function,@typescript-eslint/explicit-module-boundary-types
export const getClassicCheckoutConfig = () => {
  return {
    addressFields: ADDRESS_FIELDS_CLASSIC,

    prefixBilling: `${FIELD_PREFIX_BILLING}_`,
    prefixShipping: `${FIELD_PREFIX_SHIPPING}_`,

    fieldShippingMethod: FIELD_SHIPPING_METHOD,
    shippingMethodFormField: `${FIELD_SHIPPING_METHOD}[0]`,

    config: {
      formData: {
        [PdkField.AddressType]: 'ship_to_different_address',
        [PdkField.ShippingMethod]: `${FIELD_SHIPPING_METHOD}[0]`,
        [AddressType.Billing]: createFields(ADDRESS_FIELDS_CLASSIC, (val) => `${FIELD_PREFIX_BILLING}_${val}`),
        [AddressType.Shipping]: createFields(ADDRESS_FIELDS_CLASSIC, (val) => `${FIELD_PREFIX_SHIPPING}_${val}`),
      },

      fields: {
        [PdkField.AddressType]: createId('checkbox-control-0'),
        [PdkField.ShippingMethod]: createId(FIELD_SHIPPING_METHOD),
        [AddressType.Billing]: createFields(ADDRESS_FIELDS_CLASSIC, (val) =>
          createName(`${FIELD_PREFIX_BILLING}_${val}`),
        ),
        [AddressType.Shipping]: createFields(ADDRESS_FIELDS_CLASSIC, (val) =>
          createName(`${FIELD_PREFIX_SHIPPING}_${val}`),
        ),
      },

      formChange(callback) {
        jQuery(this.getForm?.()).on('change', () => {
          callback();
        });
      },

      getForm() {
        const getElement = useUtil(PdkUtil.GetElement);

        // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
        return getElement('form[name="checkout"]')!;
      },

      getFormData() {
        const form = useConfig().getForm();
        const formData = new FormData(form);

        return Object.fromEntries(formData.entries());
      },

      hasAddressType(addressType: AddressType) {
        const billingElement = document.querySelector('.woocommerce-billing-fields__field-wrapper');

        return AddressType.Shipping === addressType || billingElement !== null;
      },

      initialize() {
        return new Promise((resolve) => {
          jQuery(() => {
            resolve();
          });
        });
      },
    },
  } satisfies WooCommerceCheckoutConfig;
};
