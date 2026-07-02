import {AddressType} from '@myparcel-dev/pdk-checkout-common';
import {AddressField, SeparateAddressField} from '@myparcel-dev/pdk-checkout';
import {type CheckoutConfig} from '../../types';

/**
 * All WooCommerce checkout forms on the page. Standard WooCommerce renders exactly one; the Divi 5
 * checkout renders five (one per Divi module), each with name="checkout". Callers must treat the
 * checkout as the union of these forms, never assuming a single element.
 */
const getCheckoutForms = (): HTMLFormElement[] =>
  Array.from(document.querySelectorAll<HTMLFormElement>('form[name="checkout"]'));

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
    fieldAddressType: 'ship_to_different_address',
    shippingMethodFormDataKey: 'shipping_method[0]',
    addressTypeFormDataKey: 'ship_to_different_address',

    config: {
      formChange(callback) {
        getCheckoutForms().forEach((form) => {
          jQuery(form).on('change', () => {
            callback();
          });
        });
      },

      getForm() {
        const forms = getCheckoutForms();

        // The form that carries the place-order button is the one WooCommerce (and Divi) actually
        // submits, so our hidden delivery-options input must live there. On a normal single-form
        // checkout this is simply that one form.
        return (
          forms.find((form) =>
            form.querySelector('#place_order, [name="woocommerce_checkout_place_order"]'),
          ) ?? forms[0]
        );
      },

      getFormData() {
        return getCheckoutForms().reduce<Record<string, FormDataEntryValue>>((merged, form) => {
          for (const [key, value] of new FormData(form).entries()) {
            merged[key] = value;
          }

          return merged;
        }, {});
      },

      getAddressType(value: string): AddressType {
        return value === '1' ? AddressType.Shipping : AddressType.Billing;
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
