import {EVENT_HIDE_DELIVERY_OPTIONS, EVENT_SHOW_DELIVERY_OPTIONS} from '../data';
import {setStoreValue} from '../store';
import {shippingMethodHasDeliveryOptions} from './shippingMethodHasDeliveryOptions';
import {triggerEvent} from '../triggerEvent';
import {updateDeliveryOptionsConfig} from './updateDeliveryOptionsConfig';

/**
 * Hides/shows the delivery options based on the current shipping method. Makes sure to not update the checkout
 *  unless necessary by checking if hasDeliveryOptions is true or false.
 */
export const toggleDeliveryOptions = (shippingMethod: null | string): void => {
  if (shippingMethodHasDeliveryOptions(shippingMethod)) {
    setStoreValue('hasDeliveryOptions', true);
    triggerEvent(EVENT_SHOW_DELIVERY_OPTIONS, null, document);
    updateDeliveryOptionsConfig();
  } else {
    setStoreValue('hasDeliveryOptions', false);
    triggerEvent(EVENT_HIDE_DELIVERY_OPTIONS, null, document);
  }
};
