import {
  FIELD_NAME_STREET,
  FIELD_NAME_NUMBER,
  FIELD_NAME_NUMBER_SUFFIX,
  FIELD_NAME_EORI_NUMBER,
  FIELD_NAME_VAT_NUMBER,
} from '@myparcel-woocommerce/frontend-common';
import {type AddressFields} from '@myparcel-pdk/checkout';
import {ADDRESS_FIELDS_DEFAULT, PLUGIN_NAME} from '../constants';

export const ADDRESS_FIELDS_BLOCKS_CHECKOUT = Object.freeze({
  ...ADDRESS_FIELDS_DEFAULT,

  /**
   * Our separate address fields
   */
  street: `${PLUGIN_NAME}/${FIELD_NAME_STREET}`,
  number: `${PLUGIN_NAME}/${FIELD_NAME_NUMBER}`,
  numberSuffix: `${PLUGIN_NAME}/${FIELD_NAME_NUMBER_SUFFIX}`,

  /**
   * Our VAT fields
   */
  eoriNumber: `${PLUGIN_NAME}/${FIELD_NAME_EORI_NUMBER}`,
  vatNumber: `${PLUGIN_NAME}/${FIELD_NAME_VAT_NUMBER}`,
}) satisfies AddressFields & Record<string, string>;
