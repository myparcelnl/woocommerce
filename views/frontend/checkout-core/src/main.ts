import {
  getBlocksCheckoutConfig,
  getClassicCheckoutConfig,
  isClassicCheckout,
} from '@myparcel-woocommerce/frontend-common';
import {getHighestShippingClass} from '@myparcel-woocommerce/frontend-checkout-delivery-options/src/utils/getHighestShippingClass';
import {PdkField, AddressType} from '@myparcel-dev/pdk-checkout-common';
import {createPdkCheckout, getEnabledShippingMethods} from '@myparcel-dev/pdk-checkout';
import {createName, createId, createFields, hideSeparateFields} from './utils';

const config = isClassicCheckout() ? getClassicCheckoutConfig() : getBlocksCheckoutConfig();

hideSeparateFields();

createPdkCheckout({
  fields: {
    [PdkField.AddressType]: isClassicCheckout()
      ? createName(config.fieldAddressType)
      : createId(config.fieldAddressType),
    [PdkField.ShippingMethod]: createId(config.fieldShippingMethod),
    [AddressType.Billing]: createFields(
      config.addressFields,
      config.prefixBilling,
      isClassicCheckout() ? createName : createId,
    ),
    [AddressType.Shipping]: createFields(
      config.addressFields,
      config.prefixShipping,
      isClassicCheckout() ? createName : createId,
    ),
  },

  formData: {
    [PdkField.AddressType]: config.addressTypeFormDataKey,
    [PdkField.ShippingMethod]: config.shippingMethodFormDataKey,
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

  hasDeliveryOptions(shippingMethod) {
    const shippingMethods = getEnabledShippingMethods();

    const shippingMethodHasDeliveryOptions = shippingMethods.some((method) => {
      return shippingMethod === method || shippingMethod.startsWith(`${method}:`);
    });

    if (shippingMethodHasDeliveryOptions) {
      return true;
    }

    const shippingClass = getHighestShippingClass();

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
