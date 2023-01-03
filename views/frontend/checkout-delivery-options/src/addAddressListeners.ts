import {FIELD_COUNTRY, FIELD_POSTCODE} from './data';
import {getAddressField} from './utils';
import {getAddressType} from './getAddressType';
import {getSplitField} from './getSplitField';
import {getStoreValue} from './store';
import {updateAddress} from './updateAddress';

/**
 * Add listeners to the address fields remove them before adding new ones if they already exist, then update
 *  shipping method and delivery options if needed.
 *
 * Uses the country field's parent row because there is no better way to catch the select2 (or selectWoo) events as
 *  we never know when the select is loaded and can't add a normal change event. The delivery options has a debounce
 *  function on the update event so it doesn't matter if we send 5 updates at once.
 */
export const addAddressListeners = () => {
  const fields = [FIELD_COUNTRY, FIELD_POSTCODE, getSplitField()];

  /* If address type is already set, remove the existing listeners before adding new ones. */
  if (getStoreValue('addressType')) {
    fields.forEach((field) => {
      getAddressField(field)?.removeEventListener('change', updateAddress);
    });
  }

  getAddressType();

  fields.forEach((field) => {
    getAddressField(field)?.addEventListener('change', updateAddress);
  });

  updateAddress();
};
