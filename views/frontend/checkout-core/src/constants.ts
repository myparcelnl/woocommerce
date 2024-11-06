import {
  FIELD_NAME_ADDRESS_1,
  FIELD_NAME_ADDRESS_2,
  FIELD_NAME_CITY,
  FIELD_NAME_COUNTRY,
  FIELD_NAME_POSTAL_CODE,
} from '@myparcel-woocommerce/frontend-common';
import {AddressField} from '@myparcel-pdk/checkout';

export const PLUGIN_NAME = 'myparcelnl';

export const ADDRESS_FIELDS_DEFAULT = Object.freeze({
  [AddressField.Address1]: FIELD_NAME_ADDRESS_1,
  [AddressField.Address2]: FIELD_NAME_ADDRESS_2,
  [AddressField.City]: FIELD_NAME_CITY,
  [AddressField.Country]: FIELD_NAME_COUNTRY,
  [AddressField.PostalCode]: FIELD_NAME_POSTAL_CODE,
});

export const SELECTOR_FORM_CLASSIC = 'form[name="checkout"]';

export const SELECTOR_FORM_BLOCKS = '.wc-block-checkout__form';
