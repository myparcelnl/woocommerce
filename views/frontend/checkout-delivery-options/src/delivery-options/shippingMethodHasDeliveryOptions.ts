import {getSelectedShippingMethod} from '../getSelectedShippingMethod';

/**
 * Check if the given shipping method is allowed to have delivery options by checking if the name starts with any
 * value in a list of shipping methods.
 *
 * Most of the values in this list will be full shipping method names, with an instance id, but some can't have one.
 * That's the reason we're checking if it starts with this value instead of whether it's equal.
 *
 * @param {?String} shippingMethod
 * @returns {Boolean}
 */
export const shippingMethodHasDeliveryOptions = (shippingMethod: null | string) => {
  shippingMethod ??= getSelectedShippingMethod();

  let display = false;

  if (!shippingMethod) {
    return false;
  }

  if (shippingMethod.startsWith('free_shipping')) {
    shippingMethod = 'free_shipping';
  }

  /**
   * If "all" is selected for allowed shipping methods check if the current method is NOT in the
   *  disallowedShippingMethods array.
   */
  const list = window.MyParcelNLData.alwaysShow
    ? window.MyParcelNLData.disallowedShippingMethods
    : window.MyParcelNLData.allowedShippingMethods;

  list.forEach((method) => {
    const currentMethodIsAllowed = shippingMethod.includes(method);

    if (currentMethodIsAllowed) {
      display = true;
    }
  });

  if (window.MyParcelNLData.alwaysShow) {
    display = !display;
  }

  return display;
};
