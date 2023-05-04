import {AddressType, PdkField, Util, createPdkCheckout, useUtil} from '@myparcel-pdk/checkout/src';
import {createFields} from './utils/createFields';

// TODO: Get fields from the backend

const PREFIX_BILLING = 'billing_';
const PREFIX_SHIPPING = 'shipping_';

const FIELD_SHIPPING_METHOD = 'shipping_method';

const createName = (name: string) => `[name="${name}"]`;
const createId = (name: string) => `#${name}`;

createPdkCheckout({
  fields: {
    [PdkField.ShippingMethod]: createId('shipping_method'),
    [PdkField.ToggleAddressType]: '#ship-to-different-address-checkbox',
    [AddressType.Billing]: createFields(PREFIX_BILLING, createName),
    [AddressType.Shipping]: createFields(PREFIX_SHIPPING, createName),
  },

  formData: {
    [PdkField.ShippingMethod]: `${FIELD_SHIPPING_METHOD}[0]`,
    [PdkField.ToggleAddressType]: 'ship_to_different_address',
    [AddressType.Billing]: createFields(PREFIX_BILLING),
    [AddressType.Shipping]: createFields(PREFIX_SHIPPING),
  },

  selectors: {
    deliveryOptionsWrapper: '#mypa-delivery-options-wrapper',
    hasAddressType: '.woocommerce-billing-fields__field-wrapper',
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

  getForm() {
    const getElement = useUtil(Util.GetElement);

    // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
    return getElement('form[name="checkout"]')!;
  },

  onFormChange(callback) {
    jQuery(this.getForm()).on('change', () => {
      callback();
    });
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
