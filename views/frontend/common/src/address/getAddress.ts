import {FIELD_CITY, FIELD_COUNTRY, FIELD_POSTCODE} from '../data';
import {AddressType} from '../types';
import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';
import {getFieldValue} from '../utils';
import {getHouseNumber} from './getHouseNumber';

export const getAddress = (type?: AddressType): MyParcelDeliveryOptions.Address => {
  return {
    cc: getFieldValue(FIELD_COUNTRY, type),
    postalCode: getFieldValue(FIELD_POSTCODE, type),
    number: getHouseNumber(type),
    city: getFieldValue(FIELD_CITY, type),
  } as MyParcelDeliveryOptions.Address;
};
