import {
  AddressType,
  FIELD_CITY,
  FIELD_COUNTRY,
  FIELD_POSTCODE,
  getAddressFieldValue,
  getHouseNumber,
} from '@myparcel-woocommerce/frontend-common';
import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';

export const getDeliveryOptionsAddress = (type?: AddressType): MyParcelDeliveryOptions.Address => {
  return {
    cc: getAddressFieldValue(FIELD_COUNTRY, type),
    postalCode: getAddressFieldValue(FIELD_POSTCODE, type),
    number: getHouseNumber(type),
    city: getAddressFieldValue(FIELD_CITY, type),
  } as MyParcelDeliveryOptions.Address;
};
