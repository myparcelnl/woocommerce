import {FIELD_COUNTRY, FIELD_POSTCODE} from './data';
import {getAddressField} from './utils';
import {getAddressType} from './getAddressType';
import {getSplitField} from './getSplitField';
import {updateAddress} from './updateAddress';
import {useCheckoutStore} from './store';

/**
 * Add listeners to the address fields remove them before adding new ones if they already exist, then update
 *  shipping method and delivery options if needed.
 *
 * Uses the country field's parent row because there is no better way to catch the select2 (or selectWoo) events as
 *  we never know when the select is loaded and can't add a normal change event. The delivery options has a debounce
 *  function on the update event so it doesn't matter if we send 5 updates at once.
 */
export const addAddressListeners = (): void => {
  const fields = [FIELD_COUNTRY, FIELD_POSTCODE, getSplitField()];
  const checkout = useCheckoutStore();

  /* If address type is already set, remove the existing listeners before adding new ones. */
  if (checkout.state.addressType) {
    fields.forEach((field) => {
      getAddressField(field)?.removeEventListener('change', updateAddress);
    });
  }

  checkout.set({addressType: getAddressType()});

  fields.forEach((field) => {
    getAddressField(field)?.addEventListener('change', updateAddress);
  });

  updateAddress();
};
