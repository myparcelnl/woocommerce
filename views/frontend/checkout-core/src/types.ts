import {type PdkCheckoutConfigInput} from '@myparcel-pdk/checkout-common';
import {type AddressField} from '@myparcel-pdk/checkout';

export interface CheckoutConfig<Config extends Partial<PdkCheckoutConfigInput> = Partial<PdkCheckoutConfigInput>> {
  addressFields: Record<AddressField, string>;
  config: Config;
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
