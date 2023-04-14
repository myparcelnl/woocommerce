import {FrontendPdkEndpointObject} from '@myparcel-pdk/checkout/src';
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
  actions: {
    baseUrl: string;
    endpoints: FrontendPdkEndpointObject;
  };
  allowedShippingMethods: string[];
  carriersWithTaxFields: string[];
  hasDeliveryOptions: boolean;
  hiddenInputName: string;
  splitAddressFieldsCountries: string[];
};
