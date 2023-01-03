import {EVENT_WOOCOMMERCE_UPDATED_CHECKOUT, EVENT_WOOCOMMERCE_UPDATE_CHECKOUT} from '../data';
import {isOfType} from '@myparcel/ts-utils';
import {triggerEvent} from '../triggerEvent';
import {updateShippingMethod} from '../updateShippingMethod';

/**
 * When the delivery options are updated, fill the hidden input with the new data and trigger the WooCommerce
 *  update_checkout event.
 *
 * @param {CustomEvent} event - The update event.
 */
export const onDeliveryOptionsUpdate: EventListener = (event): void => {
  hiddenDataInput.value = isOfType<CustomEvent>(event, 'detail') ? JSON.stringify(event.detail) : '';

  /**
   * Remove this event before triggering and re-add it after because it will cause an infinite loop otherwise.
   */
  jQuery(document.body).off(EVENT_WOOCOMMERCE_UPDATED_CHECKOUT, updateShippingMethod);
  triggerEvent(EVENT_WOOCOMMERCE_UPDATE_CHECKOUT);

  const restoreEventListener = () => {
    jQuery(document.body).on(EVENT_WOOCOMMERCE_UPDATED_CHECKOUT, updateShippingMethod);
    jQuery(document.body).off(EVENT_WOOCOMMERCE_UPDATED_CHECKOUT, restoreEventListener);
  };

  jQuery(document.body).on(EVENT_WOOCOMMERCE_UPDATED_CHECKOUT, restoreEventListener);

  /**
   * After the "updated_checkout" event the shipping methods will be rendered, restore the event listener and delete
   *  this one in the process.
   */
  jQuery(document.body).on(EVENT_WOOCOMMERCE_UPDATED_CHECKOUT, restoreEventListener);
};
