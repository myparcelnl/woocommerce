import {type PdkCheckoutConfigInput} from '@myparcel-pdk/checkout-common';
import {type AddressField} from '@myparcel-pdk/checkout';

export interface WooCommerceCheckoutConfig {
  addressFields: Record<AddressField | string, string>;
  config: Partial<PdkCheckoutConfigInput>;
  fieldShippingMethod: string;
  prefixBilling: string;
  prefixShipping: string;
  shippingMethodFormField: string;
}

export interface WpStore {}

export interface WcShippingRate {
  rate_id: string;
  selected: boolean;
}
