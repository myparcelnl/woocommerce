import {
  AddressType,
  FIELD_ADDRESS_1,
  FIELD_CITY,
  FIELD_COUNTRY,
  FIELD_POSTCODE,
  getAddressFieldValue,
} from '@myparcel-woocommerce/frontend-common';

export const getDeliveryOptionsAddress = (
  type?: AddressType,
): {
  cc?: string;
  postalCode?: string;
  street?: string;
  city?: string;
} => {
  return {
    cc: getAddressFieldValue(FIELD_COUNTRY, type),
    postalCode: getAddressFieldValue(FIELD_POSTCODE, type),
    street: getAddressFieldValue(FIELD_ADDRESS_1, type),
    city: getAddressFieldValue(FIELD_CITY, type),
  };
};
