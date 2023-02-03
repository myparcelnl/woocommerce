import {FIELD_CITY, FIELD_POSTCODE} from '../data';
import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';
import {setFieldValue} from '../dom/setFieldValue';
import {setHouseNumber} from '../setHouseNumber';

/**
 * Set the values of the WooCommerce fields from delivery options data.
 */
export const setAddressFromDeliveryOptions = (
  address: Partial<MyParcelDeliveryOptions.Address> | null = null,
): void => {
  address = address ?? {};

  if (address.postalCode) {
    setFieldValue(FIELD_POSTCODE, address.postalCode);
  }

  if (address.city) {
    setFieldValue(FIELD_CITY, address.city);
  }

  if (address.number) {
    setHouseNumber(address.number);
  }
};
