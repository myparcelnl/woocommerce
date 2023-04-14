import {isOfType} from '@myparcel/ts-utils';
import {useCheckoutStore} from '@myparcel-woocommerce/frontend-common';

/**
 * When the delivery options are updated, fill the hidden input with the new data and trigger the WooCommerce
 *  update_checkout event.
 *
 * @param {CustomEvent} event - The update event.
 */
export const onDeliveryOptionsUpdate: EventListener = (event): void => {
  const checkout = useCheckoutStore();

  if (checkout.state.hiddenInput && isOfType<CustomEvent>(event, 'detail')) {
    const htmlInput = checkout.state.hiddenInput;
    htmlInput.value = JSON.stringify(event.detail);
    checkout.set({
      hiddenInput: htmlInput,
    });
  }
};
