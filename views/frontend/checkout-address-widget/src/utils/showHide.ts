import {WOOC_CHECKOUT_PREFIXES, WOOC_ADDRESS_FIELDS} from '../constants/fields';

/**
 * Hide all the configured woocommerce address fields
 */
export const hideAddressFields = (prefix?: string): void => {
  let prefixes: string[];

  if (prefix) {
    prefixes = [prefix];
  } else {
    prefixes = WOOC_CHECKOUT_PREFIXES;
  }

  prefixes.forEach((prefix) => {
    WOOC_ADDRESS_FIELDS.forEach((field) => {
      const woocField = document.querySelector(`#${prefix}${field}`);

      if (woocField) {
        (woocField as HTMLElement).classList.add('address-widget-forced-hidden');
      }
    });
  });
};

/**
 * Show all the configured woocommerce address fields
 * @param prefix
 */
export const showAddressFields = (prefix?: string): void => {
  let prefixes: string[];

  if (prefix) {
    prefixes = [prefix];
  } else {
    prefixes = WOOC_CHECKOUT_PREFIXES;
  }

  prefixes.forEach((prefix) => {
    WOOC_ADDRESS_FIELDS.forEach((field) => {
      const woocField = document.querySelector(`#${prefix}${field}`);

      if (woocField) {
        (woocField as HTMLElement).style.display = 'block';
      }
    });
  });
};
