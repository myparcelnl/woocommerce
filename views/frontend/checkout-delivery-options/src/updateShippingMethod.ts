import {getStoreValue, setStoreValue} from './store';
import {FIELD_SHIPPING_METHOD} from './data';
import {getHighestShippingClass} from './getHighestShippingClass';
import {onChangeShippingMethod} from './listeners';

/**
 * Update the shipping method to the new selections. Triggers hiding/showing of the delivery options.
 */
export const updateShippingMethod = (): void => {
  let shippingMethod;

  const shippingMethodField = document.querySelectorAll(FIELD_SHIPPING_METHOD);
  const selectedShippingMethodField = document.querySelector(`${FIELD_SHIPPING_METHOD}:checked`);

  /**
   * Check if shipping method field exists. It doesn't exist if there are no shipping methods available for the
   *  current address/product combination or in general.
   *
   * If there is no shipping method the delivery options will always be hidden.
   */
  if (shippingMethodField.length) {
    shippingMethod = selectedShippingMethodField ? selectedShippingMethodField.value : shippingMethodField[0].value;

    /**
     * This shipping method will have a suffix in the checkout, but this is not present in the array of
     *  selected shipping methods from the SETTING_DELIVERY_OPTIONS_DISPLAY setting.
     *
     * All variants of flat_rate (including shipping classes) do already have their suffix set properly.
     */
    if (shippingMethod.indexOf('flat_rate') === 0) {
      const shippingClass = getHighestShippingClass();

      if (shippingClass) {
        shippingMethod = `flat_rate:${shippingClass}`;
      }
    }
  } else {
    shippingMethod = null;
  }

  const selectedShippingMethod = getStoreValue('shippingMethod');

  if (shippingMethod !== selectedShippingMethod) {
    onChangeShippingMethod(selectedShippingMethod, shippingMethod);
    setStoreValue('shippingMethod', shippingMethod);
  }
};
