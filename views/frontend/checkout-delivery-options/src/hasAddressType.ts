import {AddressType} from './types';

/**
 * Checks if the inner wrapper of an address type form exists to determine if the address type is available.
 *
 * Does not check the outer div (.woocommerce-shipping-fields) because when the shipping form does not exist, it's
 *  still rendered on the page.
 */
export const hasAddressType = (addressType: AddressType): boolean => {
  const formWrapper = document.querySelector(`.woocommerce-${addressType}-fields__field-wrapper`);

  return Boolean(formWrapper);
};
