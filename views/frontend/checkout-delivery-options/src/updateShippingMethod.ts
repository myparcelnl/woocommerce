import {FIELD_SHIPPING_METHOD, getElement} from '@myparcel-woocommerce/frontend-common';
import {onChangeShippingMethod} from './listeners';

/**
 * Update the shipping method to the new selections. Triggers hiding/showing of the delivery options.
 */
export const updateShippingMethod = (): void => {
  let shippingMethod: string | undefined;

  const shippingMethodField = getElement<HTMLInputElement>(FIELD_SHIPPING_METHOD);
  const selectedShippingMethodField = getElement<HTMLInputElement>(`${FIELD_SHIPPING_METHOD}:checked`);

  /**
   * Check if shipping method field exists. It doesn't exist if there are no shipping methods available for the
   *  current address/product combination or in general.
   *
   * If there is no shipping method the delivery options will always be hidden.
   */
  if (shippingMethodField) {
    shippingMethod = selectedShippingMethodField ? selectedShippingMethodField?.value : shippingMethodField?.value;

    /**
     * This shipping method will have a suffix in the checkout, but this is not present in the array of
     *  selected shipping methods from the SETTING_DELIVERY_OPTIONS_DISPLAY setting.
     *
     * All variants of flat_rate (including shipping classes) do already have their suffix set properly.
     */
    if (shippingMethod.startsWith('flat_rate')) {
      // const shippingClass = getHighestShippingClass();
      const shippingClass = '1';

      if (shippingClass) {
        shippingMethod = `flat_rate:${shippingClass}`;
      }
    }
  }

  onChangeShippingMethod(shippingMethod);
};
