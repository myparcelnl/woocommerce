import {isClassicCheckout} from '@myparcel-woocommerce/frontend-common';

/**
 * Hides the separate address fields (street name, house number, house number suffix)
 * for both billing and shipping addresses as a precaution, for they can be shown,
 * even when switched off, in the case the country is NULL, which happens when the
 * WooCommerce setting "Default customer address" is set to "None".
 *
 * Note: you cannot obtain the fields from the config, because they are not present
 * when switched off, even though they are still in the DOM when country is NULL.
 * We cannot remove them server-side (so they are not in the DOM) in case of NULL,
 * because they may be required later client-side.
 */
export const hideSeparateFields = (): void => {
  // if a country is known there is no need to hide the fields pre-emptively
  const countrySelect = document.getElementById(
    isClassicCheckout() ? 'billing_country' : 'shipping-country',
  ) as HTMLInputElement;

  if (countrySelect?.value) {
    return;
  }

  const fields = ['street_name', 'house_number', 'house_number_suffix'];

  if (isClassicCheckout()) {
    const prefixes = ['billing', 'shipping'];

    prefixes.forEach((prefix) => {
      fields.forEach((field) => {
        const el = document.getElementById(`${prefix}_${field}_field`);

        if (el) {
          el.style.display = 'none';
        }
      });
    });
  }

  if (!isClassicCheckout()) {
    const ids = ['billing-fields', 'shipping-fields'];
    const prefix = '.wc-block-components-text-input.wc-block-components-address-form__myparcelnl-';

    ids.forEach((id) => {
      const container = document.getElementById(id);

      if (container) {
        fields.forEach((field) => {
          const el = container.querySelector(`${prefix}${field}`);

          if (el) {
            (el as HTMLElement).style.display = 'none';
          }
        });
      }
    });
  }
};
