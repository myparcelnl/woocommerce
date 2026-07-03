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

/**
 * True when `element` or any ancestor is hidden via `display:none`. Divi 5 hides its duplicate
 * billing fieldset by setting `display:none` on a container, not on the inputs, so a check of the
 * control's own computed display would miss it — we must walk ancestors. Uses getComputedStyle per
 * node because Element.checkVisibility() and offsetParent are unavailable under happy-dom 14.
 */
const isHidden = (element: Element): boolean => {
  for (let node: Element | null = element; node && node !== document.body; node = node.parentElement) {
    if (window.getComputedStyle(node).display === 'none') {
      return true;
    }
  }

  return false;
};

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
        // isClassicCheckout() guarantees at least one form[name="checkout"] exists, so forms[0]! is safe.
        return (
          forms.find((form) =>
            form.querySelector('#place_order, [name="woocommerce_checkout_place_order"]'),
          ) ?? forms[0]!
        );
      },

      getFormData() {
        return getCheckoutForms().reduce<Record<string, FormDataEntryValue>>((merged, form) => {
          // Divi 5 renders a hidden duplicate of the billing fieldset in the additional-info module.
          // Its controls aren't disabled, so FormData still includes them, and because that form
          // comes later in DOM order it would clobber the visible form's live values. Collect the
          // names of controls hidden by a display:none ancestor and skip them. Iterating
          // form.elements avoids CSS-selector escaping of names like "shipping_method[0]".
          const hiddenNames = new Set<string>();

          for (const control of Array.from(form.elements)) {
            if (control instanceof HTMLElement && control.getAttribute('name') && isHidden(control)) {
              hiddenNames.add(control.getAttribute('name')!);
            }
          }

          for (const [key, value] of new FormData(form).entries()) {
            if (hiddenNames.has(key)) {
              continue;
            }

            merged[key] = value;
          }

          return merged;
        }, {});
      },

      // The value we're handed lags one form-read behind the DOM (js-pdk updateCheckoutForm),
      // and an unchecked box is omitted from FormData. Read the live checkbox instead.
      getAddressType(): AddressType {
        const checkbox = getCheckoutForms()
          .flatMap((form) =>
            Array.from(form.querySelectorAll<HTMLInputElement>('input[name="ship_to_different_address"]')),
          )
          .find((input) => !isHidden(input));

        return checkbox?.checked ? AddressType.Shipping : AddressType.Billing;
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
