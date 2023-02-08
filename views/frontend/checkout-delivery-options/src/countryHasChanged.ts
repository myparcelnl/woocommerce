import {FIELD_COUNTRY, getAddressField, useDeliveryOptionsStore} from '@myparcel-woocommerce/frontend-common';

/**
 * Check if the country changed by comparing the old value with the new value before overwriting the MyParcelConfig
 *  with the new value. Returns true if none was set yet.
 *
 * @returns {Boolean}
 */
export const countryHasChanged = (): boolean => {
  const deliveryOptions = useDeliveryOptionsStore();

  if (deliveryOptions.state.address?.hasOwnProperty('cc')) {
    return deliveryOptions.state.address.cc !== getAddressField(FIELD_COUNTRY)?.value;
  }

  return true;
};
