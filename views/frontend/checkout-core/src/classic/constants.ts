import {
  FIELD_NAME_STREET,
  FIELD_NAME_NUMBER,
  FIELD_NAME_NUMBER_SUFFIX,
  FIELD_NAME_EORI_NUMBER,
  FIELD_NAME_VAT_NUMBER,
} from '@myparcel-woocommerce/frontend-common';
import {type AddressFields} from '@myparcel-pdk/checkout';
import {ADDRESS_FIELDS_DEFAULT} from '../constants';

export const ADDRESS_FIELDS_CLASSIC = Object.freeze({
  ...ADDRESS_FIELDS_DEFAULT,

  /**
   * Our separate address fields
   */
  street: FIELD_NAME_STREET,
  number: FIELD_NAME_NUMBER,
  numberSuffix: FIELD_NAME_NUMBER_SUFFIX,

  /**
   * Our VAT fields
   */
  eoriNumber: FIELD_NAME_EORI_NUMBER,
  vatNumber: FIELD_NAME_VAT_NUMBER,
} satisfies AddressFields & Record<string, string>);
