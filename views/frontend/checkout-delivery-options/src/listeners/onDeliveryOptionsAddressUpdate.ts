import {isOfType} from '@myparcel/ts-utils';
import {setAddressFromDeliveryOptions} from '../delivery-options';

/**
 * When the delivery options module has updated the address, using the "retry" option.
 */
export const onDeliveryOptionsAddressUpdate: EventListener = (event) => {
  if (!isOfType<CustomEvent>(event, 'detail')) {
    return;
  }

  setAddressFromDeliveryOptions(event.detail);
};
