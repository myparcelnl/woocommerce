import {
  isClassicCheckout,
  getClassicCheckoutConfig,
  getBlocksCheckoutConfig,
} from '@myparcel-woocommerce/frontend-common';

const config = isClassicCheckout() ? getClassicCheckoutConfig() : getBlocksCheckoutConfig();

export const WOOC_ADDRESS_FIELDS = [
  'address_1_field',
  'address_2_field',
  'street_field',
  'city_field',
  'postcode_field',
  'state_field',
];

export const SPLIT_ADDRESS_FIELDS = ['street_name_field', 'house_number_field', 'house_number_suffix_field'];

export const ALL_ADDRESS_FIELDS = [...WOOC_ADDRESS_FIELDS, ...SPLIT_ADDRESS_FIELDS];

export const WOOC_CHECKOUT_PREFIXES = [config.prefixBilling, config.prefixShipping];

export const WOOC_COUNTRY_FIELD = 'country';
