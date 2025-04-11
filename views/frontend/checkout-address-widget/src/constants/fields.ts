import {getClassicCheckoutConfig} from '../../../checkout-core/src/classic';

// TODO: get from PDK if possible
export const WOOC_ADDRESS_FIELDS = [
  'address_1_field',
  'address_2_field',
  'street_field',
  'city_field',
  'postcode_field',
  'state_field',
];

export const HIDDEN_ADDRESS_FIELD = 'myparcel_address';

export const WOOC_CHECKOUT_PREFIXES = [
  getClassicCheckoutConfig().prefixBilling,
  getClassicCheckoutConfig().prefixShipping,
];

export const WOOC_COUNTRY_FIELD = 'country';
