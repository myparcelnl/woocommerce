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
  /**
   * Blocks checkout is handled in SeparateAddressFieldsHooks.php ->registerAdditionalBlocksCheckoutFields
   * Classic checkout must be handled here.
   */
  if (isClassicCheckout()) {
    // Check if separate address fields are enabled by looking for the fields in the DOM
    // If the fields exist and are visible, it means separate address fields are enabled
    const testField = document.getElementById('billing_street_name_field');
    const fieldsExist = testField !== null;

    // Only hide fields if they don't exist in the DOM (meaning separate address fields are disabled)
    if (!fieldsExist) {
      const fields = ['street_name', 'house_number', 'house_number_suffix'];
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
  }
};
