import {type TaxField, type SeparateAddressField, type AddressField} from '@myparcel-pdk/checkout';

export interface WpStore {}

export interface WcShippingRate {
  rate_id: string;
  selected: boolean;
}

export type WcAddressField = 'address_1' | 'address_2' | 'city' | 'postcode' | 'country' | 'state' | string;

export type WcAddressObject = Record<WcAddressField, string>;

export type AnyPdkAddressField = AddressField | SeparateAddressField | TaxField;
