import {getClassicCheckoutConfig} from '../../../checkout-core/src/classic';

// TODO: get from PDK if possible
export const woocAddressFields = [
  'address_1_field',
  'address_2_field',
  'street_field',
  'city_field',
  'postcode_field',
  'state_field',
];

export const woocAddressFieldPrefixes = [
  getClassicCheckoutConfig().prefixBilling,
  getClassicCheckoutConfig().prefixShipping,
];

export const woocCountryField = 'country';
