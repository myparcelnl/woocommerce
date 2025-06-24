/* eslint-disable @typescript-eslint/naming-convention */
import {type AnyConfig, type ReduxStoreConfig, type StoreInstance} from '@wordpress/data/src/types';
import {type StoreDescriptor} from '@wordpress/data';
import {type PdkCheckoutConfigInput, type PdkCheckoutForm} from '@myparcel-pdk/checkout-common';

export interface CheckoutConfig<Config extends Partial<PdkCheckoutConfigInput> = Partial<PdkCheckoutConfigInput>> {
  addressFields: PdkCheckoutForm['billing'] & PdkCheckoutForm['shipping'];
  config: Config;
  fieldShippingMethod: string;
  prefixBilling: string;
  prefixShipping: string;
  fieldAddressType: string;
  shippingMethodFormDataKey: string;
  addressTypeFormDataKey: string;
}

export interface WcShippingRate {
  rate_id: string;
  selected: boolean;
}

/**
 * WooCommerce does not seem to export types for their stores, this is a partial implementation.
 * @see https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/data-store/cart.md
 */
export type WcCustomerAddress = {
  first_name: string;
  last_name: string;
  company: string;
  address_1: string;
  address_2: string;
  city: string;
  state: string;
  postcode: string;
  country: string;
};

export type WcCustomerData = {
  shippingAddress: Partial<WcCustomerAddress>;
  billingAddress: Partial<WcCustomerAddress>;
};

export type WcCartStore = StoreInstance<
  ReduxStoreConfig<
    unknown,
    {
      updateCustomerData: (customerData: WcCustomerData, editing?: boolean) => void;
      setBillingAddress: (billingAddress: Partial<WcCustomerAddress>) => void;
      setShippingAddress: (shippingAddress: Partial<WcCustomerAddress>) => void;
    },
    {
      getCustomerData(): {
        shippingAddress: Record<string, string>;
      };
      // eslint-disable-next-line @typescript-eslint/naming-convention
      getShippingRates(): [{shipping_rates: WcShippingRate[]}];
    }
  >
>;

export type WcCheckoutStore = StoreInstance<
  ReduxStoreConfig<
    unknown,
    Record<string, never>,
    {
      getUseShippingAsBilling(): boolean;
    }
  >
>;

export interface UseWcStore<Store extends StoreInstance<AnyConfig>> {
  storeDescriptor: StoreDescriptor;
  actions: ReturnType<Store['getActions']>;
  selectors: ReturnType<Store['getSelectors']>;
}
