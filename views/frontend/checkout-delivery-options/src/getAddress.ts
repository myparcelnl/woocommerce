import {FIELD_CITY, FIELD_COUNTRY, FIELD_POSTCODE} from './data';
import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';
import {getFieldValue} from './dom/getFieldValue';
import {getHouseNumber} from './getHouseNumber';

export const getAddress = (): MyParcelDeliveryOptions.Address =>
  ({
    cc: getFieldValue(FIELD_COUNTRY),
    postalCode: getFieldValue(FIELD_POSTCODE),
    number: getHouseNumber(),
    city: getFieldValue(FIELD_CITY),
  } as MyParcelDeliveryOptions.Address);
