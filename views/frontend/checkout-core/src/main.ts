import {AddressType, PdkField, Util, createPdkCheckout, useUtil} from '@myparcel-pdk/checkout/src';
import {createFields} from './utils/createFields';

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

  getForm: () => {
    const getElement = useUtil(Util.GetElement);

    // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
    return getElement('form[name="checkout"]')!;
  },

  initialize: () => {
    return new Promise((resolve) => {
      jQuery(() => {
        resolve();
      });
    });
  },

  selectors: {
    deliveryOptionsWrapper: '#mypa-delivery-options-wrapper',
    hasAddressType: '.woocommerce-billing-fields__field-wrapper',
  },
});
