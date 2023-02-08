import {MyParcelDeliveryOptions} from '@myparcel/delivery-options';

export enum AddressType {
  BILLING = 'billing',
  SHIPPING = 'shipping',
}

export interface FrontendAppContext {
  checkout: {
    config: MyParcelDeliveryOptions.Config;
    strings: MyParcelDeliveryOptions.Strings;
    settings: FrontendSettings;
  };
}

export type FrontendSettings = {
  addressType: AddressType | null;
  ajaxHookFetchContext: string;
  ajaxUrl: string;
  allowedShippingMethods: string[];
  alwaysShow: boolean;
  disallowedShippingMethods: string[];
  hasDeliveryOptions: boolean;
  hasSplitAddressFields: boolean;
  hiddenInputName: string;
  shippingMethod: string | null;
  splitAddressFieldsCountries: string[];
};
