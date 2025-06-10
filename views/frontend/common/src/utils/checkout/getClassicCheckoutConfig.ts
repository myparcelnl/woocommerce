import {AddressType, useConfig} from '@myparcel-pdk/checkout-common';
import {useUtil, AddressField, PdkUtil, SeparateAddressField} from '@myparcel-pdk/checkout';
import {type CheckoutConfig} from '../../types';

// eslint-disable-next-line max-lines-per-function
export const getClassicCheckoutConfig = (): CheckoutConfig => {
  return {
    addressFields: {
      [AddressField.Address1]: `address_1`,
      [AddressField.Address2]: `address_2`,
      [AddressField.City]: `city`,
      [AddressField.Country]: `country`,
      [AddressField.PostalCode]: `postcode`,
      [SeparateAddressField.Street]: `street_name`,
      [SeparateAddressField.Number]: `house_number`,
      [SeparateAddressField.NumberSuffix]: `house_number_suffix`,
    },

    prefixBilling: 'billing_',
    prefixShipping: 'shipping_',

    fieldShippingMethod: 'shipping_method',
    shippingMethodFormField: 'shipping_method[0]',

    config: {
      formChange(callback) {
        jQuery(this.getForm()).on('change', () => {
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
  } satisfies CheckoutConfig;
};
