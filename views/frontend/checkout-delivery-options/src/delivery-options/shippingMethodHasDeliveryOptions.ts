import {getSelectedShippingMethod} from '../getSelectedShippingMethod';
import {useSettingsStore} from '../store';

export const METHOD_FREE_SHIPPING = 'free_shipping';

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
export const shippingMethodHasDeliveryOptions = (shippingMethod: null | string): boolean => {
  shippingMethod ??= getSelectedShippingMethod();

  if (!shippingMethod) {
    return false;
  }

  if (shippingMethod.startsWith(METHOD_FREE_SHIPPING)) {
    shippingMethod = METHOD_FREE_SHIPPING;
  }

  const settings = useSettingsStore();

  /**
   * If "all" is selected for allowed shipping methods check if the current method is NOT in the
   *  disallowedShippingMethods array.
   */
  const list = settings.state.alwaysShow
    ? settings.state.disallowedShippingMethods
    : settings.state.allowedShippingMethods;

  const comparison = settings.state.alwaysShow ? 'every' : 'some';

  return list[comparison]((method) => shippingMethod?.includes(method));
};
