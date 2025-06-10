import {type PdkCheckoutConfigInput, type PdkCheckoutForm} from '@myparcel-pdk/checkout-common';

export interface CheckoutConfig<Config extends Partial<PdkCheckoutConfigInput> = Partial<PdkCheckoutConfigInput>> {
  addressFields: PdkCheckoutForm['billing'] & PdkCheckoutForm['shipping'];
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
