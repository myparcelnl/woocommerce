import {EVENT_UPDATE_DELIVERY_OPTIONS} from './data';
import {getStoreValue} from './store';
import {triggerEvent} from './triggerEvent';
import {getAddress} from './getAddress';

/**
 * Get data from form fields, put it in the global MyParcelConfig, then trigger updating the delivery options.
 */
export const updateAddress = (): void => {
  // window.MyParcelConfig.address = {
  //   cc: getAddressField(FIELD_COUNTRY).value,
  //   postalCode: getAddressField(FIELD_POSTCODE).value,
  //   number: getHouseNumber(),
  //   city: getAddressField(FIELD_CITY).value,
  // };

  if (getStoreValue('hasDeliveryOptions')) {
    triggerEvent(EVENT_UPDATE_DELIVERY_OPTIONS, {
      address: getAddress(),
    });
  }
};
