import {FIELD_CITY, FIELD_COUNTRY, FIELD_POSTCODE} from './data';
import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';
import {getAddressField} from './utils';
import {getHouseNumber} from './getHouseNumber';

export const getAddress = (): Partial<MyParcelDeliveryOptions.Address> => ({
  cc: getAddressField(FIELD_COUNTRY)?.value,
  postalCode: getAddressField(FIELD_POSTCODE)?.value,
  number: getHouseNumber(),
  city: getAddressField(FIELD_CITY)?.value,
});
