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
 * True when `element` or any ancestor is hidden via `display:none`. Used to locate the single
 * *visible, interactive* control among Divi's duplicates — notably the real ship_to_different_address
 * checkbox. Divi renders extra copies both inside `display:none` containers and as self-hidden
 * inputs (own display `none`), and neither should be mistaken for the one the user toggles, so the
 * element's own computed display counts here. Uses getComputedStyle per node because
 * Element.checkVisibility() and offsetParent are unavailable under happy-dom 14.
 */
const isHidden = (element: Element): boolean => {
  for (let node: Element | null = element; node && node !== document.body; node = node.parentElement) {
    if (window.getComputedStyle(node).display === 'none') {
      return true;
    }
  }

  return false;
};

/**
 * True when an *ancestor container* of `element` is hidden via `display:none`. Divi 5 hides its
 * duplicate billing fieldset by setting `display:none` on a container, not on the inputs, and that
 * container is what marks the duplicate as a non-authoritative copy — so we walk ancestors starting
 * from the parent and deliberately ignore the element's own display. This is what lets getFormData
 * drop the duplicate fieldset while KEEPING legitimately hidden inputs like WooCommerce's single
 * available shipping method, which it renders as `<input type="hidden" name="shipping_method[0]">`
 * (own display `none`) yet carries a real value that must survive the merge.
 */
const isInHiddenContainer = (element: Element): boolean => {
  for (let node: Element | null = element.parentElement; node && node !== document.body; node = node.parentElement) {
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

        // WooCommerce re-renders the shipping-method radios via an AJAX update_checkout (fired e.g.
        // when ship_to_different_address is toggled or the address changes) and auto-selects a radio
        // WITHOUT emitting a bubbling `change`. Without re-syncing here the store keeps the shipping
        // method it read mid-render — often empty when the new country's rates aren't auto-checked —
        // which flips delivery options to disabled and never recovers. `updated_checkout` marks that
        // re-render complete; the checkout store's set() is guarded by an equality check, so calling
        // back when nothing changed is a safe no-op.
        jQuery(document.body).on('updated_checkout', () => {
          callback();
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
          // names of controls sitting inside a display:none *container* and skip them — but not
          // controls that are merely hidden by their own type (e.g. WooCommerce's single available
          // shipping method, rendered as <input type="hidden" name="shipping_method[0]">), which
          // carry real values. Iterating form.elements avoids CSS-selector escaping of names like
          // "shipping_method[0]".
          const hiddenNames = new Set<string>();

          for (const control of Array.from(form.elements)) {
            if (control instanceof HTMLElement && control.getAttribute('name') && isInHiddenContainer(control)) {
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
