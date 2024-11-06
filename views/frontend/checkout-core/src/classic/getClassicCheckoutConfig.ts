import {FIELD_PREFIX_BILLING, FIELD_PREFIX_SHIPPING} from '@myparcel-woocommerce/frontend-common';
import {AddressType, useConfig, PdkField, type PdkCheckoutConfigInput} from '@myparcel-pdk/checkout-common';
import {useUtil, PdkUtil} from '@myparcel-pdk/checkout';
import {createId, createFields, createName} from '../utils';
import {SELECTOR_FORM_CLASSIC} from '../constants';
import {ADDRESS_FIELDS_CLASSIC} from './constants';

// eslint-disable-next-line max-lines-per-function,@typescript-eslint/explicit-module-boundary-types
export const getClassicCheckoutConfig = () => {
  return {
    fields: {
      // Irrelevant because there's always a separate billing and shipping address object.
      [PdkField.AddressType]: '',
      [PdkField.ShippingMethod]: createId('shipping_method[0]'),
      [AddressType.Billing]: createFields(ADDRESS_FIELDS_CLASSIC, (val) =>
        createName(`${FIELD_PREFIX_BILLING}_${val}`),
      ),
      [AddressType.Shipping]: createFields(ADDRESS_FIELDS_CLASSIC, (val) =>
        createName(`${FIELD_PREFIX_SHIPPING}_${val}`),
      ),
    },

    formData: {
      [PdkField.AddressType]: PdkField.AddressType,
      [PdkField.ShippingMethod]: 'shipping_method',
      [AddressType.Billing]: createFields(ADDRESS_FIELDS_CLASSIC, (val) => `${FIELD_PREFIX_BILLING}_${val}`),
      [AddressType.Shipping]: createFields(ADDRESS_FIELDS_CLASSIC, (val) => `${FIELD_PREFIX_SHIPPING}_${val}`),
    },

    formChange(callback) {
      jQuery(this.getForm?.()).on('change', () => {
        callback();
      });
    },

    getForm() {
      const getElement = useUtil(PdkUtil.GetElement);

      // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
      return getElement(SELECTOR_FORM_CLASSIC)!;
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
  } satisfies Partial<PdkCheckoutConfigInput>;
};
