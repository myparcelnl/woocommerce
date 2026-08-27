import {AddressType} from '@myparcel-dev/pdk-checkout-common';
import {AddressField, SeparateAddressField} from '@myparcel-dev/pdk-checkout';
import {type CheckoutConfig} from '../../types';

/**
 * All `form[name="checkout"]` on the page. Standard WooCommerce renders one; Divi 5 renders five (one
 * per module), so treat the checkout as their union rather than a single form.
 */
const getCheckoutForms = (): HTMLFormElement[] =>
  Array.from(document.querySelectorAll<HTMLFormElement>('form[name="checkout"]'));

/**
 * True if `start` or an ancestor is `display:none`, via native checkVisibility() (supported in all
 * evergreen browsers; polyfilled for happy-dom in tests). Without options it considers display only
 * (visibility/opacity ignored).
 */
const hasDisplayNoneAncestor = (start: Element | null): boolean => (start ? !start.checkVisibility() : false);

/**
 * Element or an ancestor is hidden. Own display counts, so this picks the one *visible* control among
 * Divi's duplicate ship_to_different_address checkboxes (Divi also renders self-hidden copies).
 */
const isHidden = (element: Element): boolean => hasDisplayNoneAncestor(element);

/**
 * An *ancestor container* is hidden (own display ignored). Divi hides its duplicate billing fieldset on
 * a container, not the inputs; this drops that fieldset while keeping self-hidden real inputs like
 * WooCommerce's single `shipping_method[0]`.
 */
const isInHiddenContainer = (element: Element): boolean => hasDisplayNoneAncestor(element.parentElement);

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

        // WooCommerce re-selects the shipping-method radio after its AJAX re-render WITHOUT a bubbling
        // `change`, so the form-level listener misses it; `updated_checkout` catches that. set() is
        // equality-guarded, so a redundant callback is a safe no-op.
        jQuery(document.body).on('updated_checkout', () => {
          callback();
        });
      },

      getForm() {
        const forms = getCheckoutForms();

        // Our hidden delivery-options input must live on the form that gets submitted: the one with the
        // place-order button. forms[0]! is safe — isClassicCheckout() guarantees at least one form.
        return (
          forms.find((form) =>
            form.querySelector('#place_order, [name="woocommerce_checkout_place_order"]'),
          ) ?? forms[0]!
        );
      },

      getFormData() {
        const visibleNames = new Set<string>();
        const formStates = getCheckoutForms().map((form) => {
          const hiddenNames = new Set<string>();

          for (const control of form.elements) {
            const name = control.getAttribute('name');

            if (!name) {
              continue;
            }

            if (isInHiddenContainer(control)) {
              hiddenNames.add(name);
            } else {
              visibleNames.add(name);
            }
          }

          return {form, hiddenNames};
        });

        // Visible names are global because Divi puts duplicate fields in separate forms. Hidden names
        // stay scoped to their form, so only the hidden duplicate is skipped. Unique hidden fields are
        // valid form values and remain in the merged data.
        return formStates.reduce<Record<string, FormDataEntryValue>>(
          (merged, {form, hiddenNames}) => {
            for (const [key, value] of new FormData(form).entries()) {
              if (hiddenNames.has(key) && visibleNames.has(key)) {
                continue;
              }

              merged[key] = value;
            }

            return merged;
          },
          {},
        );
      },

      // The value js-pdk passes lags one form-read behind and omits an unchecked box; read the live DOM.
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
