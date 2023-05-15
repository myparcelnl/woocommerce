import {AddressType, PdkField, Util, createPdkCheckout, useUtil} from '@myparcel-pdk/checkout/src';
import {createFields} from './utils/createFields';

const PREFIX_BILLING = 'billing_';
const PREFIX_SHIPPING = 'shipping_';

const FIELD_SHIPPING_METHOD = 'shipping_method';

const createName = (name: string) => `[name="${name}"]`;
const createId = (name: string) => `#${name}`;

createPdkCheckout({
  fields: {
    [PdkField.AddressType]: createId('ship-to-different-address-checkbox'),
    [PdkField.ShippingMethod]: createId(FIELD_SHIPPING_METHOD),
    [AddressType.Billing]: createFields(PREFIX_BILLING, createName),
    [AddressType.Shipping]: createFields(PREFIX_SHIPPING, createName),
  },

  formData: {
    [PdkField.AddressType]: 'ship_to_different_address',
    [PdkField.ShippingMethod]: `${FIELD_SHIPPING_METHOD}[0]`,
    [AddressType.Billing]: createFields(PREFIX_BILLING),
    [AddressType.Shipping]: createFields(PREFIX_SHIPPING),
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

  formChange(callback) {
    jQuery(this.getForm()).on('change', () => {
      callback();
    });
  },

  getAddressType(value) {
    return value === '1' ? AddressType.Shipping : AddressType.Billing;
  },

  getForm() {
    const getElement = useUtil(Util.GetElement);

    // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
    return getElement('form[name="checkout"]')!;
  },

  hasAddressType(addressType: AddressType) {
    const billingElement = document.querySelector('.woocommerce-billing-fields__field-wrapper');

    return AddressType.Shipping === addressType || billingElement !== null;
  },

  initialize() {
    return new Promise((resolve) => {
      jQuery(() => {
        resolve();
      });
    });
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
});
