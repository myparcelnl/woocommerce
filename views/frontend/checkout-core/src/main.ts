import {getHighestShippingClass} from '@myparcel-woocommerce/frontend-checkout-delivery-options/src/utils/getHighestShippingClass';
import {PdkField, AddressType} from '@myparcel-pdk/checkout-common';
import {createPdkCheckout, getEnabledShippingMethods} from '@myparcel-pdk/checkout';
import {isClassicCheckout, createName, createId, createFields} from './utils';
import {getClassicCheckoutConfig} from './classic';
import {getBlocksCheckoutConfig} from './blocks';

const config = isClassicCheckout() ? getClassicCheckoutConfig() : getBlocksCheckoutConfig();

createPdkCheckout({
  fields: {
    [PdkField.AddressType]: createId('checkbox-control-0'),
    [PdkField.ShippingMethod]: createId(config.fieldShippingMethod),
    [AddressType.Billing]: createFields(config.addressFields, config.prefixBilling, createName),
    [AddressType.Shipping]: createFields(config.addressFields, config.prefixShipping, createName),
  },

  formData: {
    [PdkField.AddressType]: 'ship_to_different_address',
    [PdkField.ShippingMethod]: config.shippingMethodFormField,
    [AddressType.Billing]: createFields(config.addressFields, config.prefixBilling),
    [AddressType.Shipping]: createFields(config.addressFields, config.prefixShipping),
  },

  selectors: {
    deliveryOptionsWrapper: '#mypa-delivery-options-wrapper',
  },

  async doRequest(endpoint) {
    const query = new URLSearchParams(endpoint.parameters).toString();

    const response = await window.fetch(`${endpoint.baseUrl}/${endpoint.path}?${query}`, {
      method: endpoint.method,
      body: endpoint.body,
    });

    if (response.ok) {
      return response.json();
    }
  },

  getAddressType(value) {
    return value === '1' ? AddressType.Shipping : AddressType.Billing;
  },

  hasDeliveryOptions(shippingMethod) {
    const shippingMethods = getEnabledShippingMethods();

    const shippingMethodHasDeliveryOptions = shippingMethods.some((method) => {
      return shippingMethod === method || shippingMethod.startsWith(`${method}:`);
    });

    if (shippingMethodHasDeliveryOptions) {
      return true;
    }

    const shippingClass = getHighestShippingClass();

    console.log(shippingClass, shippingMethods);

    return shippingClass !== undefined && shippingMethods.includes(shippingClass);
  },

  toggleField(field: HTMLInputElement, show: boolean): void {
    const $field = jQuery(field);
    const $wrapper = $field.closest('.form-row');

    if (show) {
      $wrapper.show();
    } else {
      $wrapper.hide();
    }
  },

  ...config.config,
});
