import {
  FIELD_NAME_STREET,
  FIELD_NAME_NUMBER,
  FIELD_NAME_NUMBER_SUFFIX,
  FIELD_NAME_EORI_NUMBER,
  FIELD_NAME_VAT_NUMBER,
} from '@myparcel-woocommerce/frontend-common';
import {SeparateAddressField, TaxField, type AddressField} from '@myparcel-pdk/checkout';
import {type WcAddressField} from '../types';
import {ADDRESS_FIELDS_DEFAULT, PLUGIN_NAME} from '../constants';

export const ADDRESS_FIELDS_BLOCKS_CHECKOUT = Object.freeze({
  ...ADDRESS_FIELDS_DEFAULT,

  /**
   * Our separate address fields
   */
  [SeparateAddressField.Street]: `${PLUGIN_NAME}/${FIELD_NAME_STREET}`,
  [SeparateAddressField.Number]: `${PLUGIN_NAME}/${FIELD_NAME_NUMBER}`,
  [SeparateAddressField.NumberSuffix]: `${PLUGIN_NAME}/${FIELD_NAME_NUMBER_SUFFIX}`,

  /**
   * Our tax fields
   */
  [TaxField.VatNumber]: `${PLUGIN_NAME}/${FIELD_NAME_VAT_NUMBER}`,
  [TaxField.EoriNumber]: `${PLUGIN_NAME}/${FIELD_NAME_EORI_NUMBER}`,
}) satisfies Record<AddressField | SeparateAddressField | TaxField, WcAddressField>;
