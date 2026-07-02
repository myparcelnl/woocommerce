// @vitest-environment happy-dom
import {beforeEach, describe, expect, it, vi} from 'vitest';
import {getClassicCheckoutConfig} from './getClassicCheckoutConfig';

// The config object references these enums as object keys only; simple stubs suffice.
// vi.mock is hoisted above the imports by Vitest.
vi.mock('@myparcel-dev/pdk-checkout-common', () => ({
  AddressType: {Billing: 'billing', Shipping: 'shipping'},
}));

vi.mock('@myparcel-dev/pdk-checkout', () => ({
  AddressField: {
    Address1: 'address1',
    Address2: 'address2',
    City: 'city',
    Country: 'country',
    PostalCode: 'postalCode',
  },
  SeparateAddressField: {Street: 'street', Number: 'number', NumberSuffix: 'numberSuffix'},
}));

/** A single WooCommerce checkout form: billing field + checked shipping method + submit. */
const SINGLE_FORM = `
  <form name="checkout" class="checkout woocommerce-checkout">
    <input type="text" name="billing_first_name" value="Jane" />
    <input type="radio" name="shipping_method[0]" value="flat_rate:1" checked />
    <button type="submit" id="place_order" name="woocommerce_checkout_place_order">Place order</button>
  </form>
`;

/** Divi 5: five separate name="checkout" forms; shipping method and submit in different forms. */
const DIVI_FORMS = `
  <div class="et_pb_wc_checkout_billing">
    <form name="checkout" class="checkout woocommerce-checkout">
      <div id="customer_details"></div>
      <input type="text" name="billing_first_name" value="Jane" />
    </form>
  </div>
  <div class="et_pb_wc_checkout_shipping">
    <form name="checkout" class="checkout woocommerce-checkout">
      <div id="customer_details"></div>
      <input type="checkbox" name="ship_to_different_address" value="1" />
    </form>
  </div>
  <div class="et_pb_wc_checkout_additional_info">
    <form name="checkout" class="checkout woocommerce-checkout">
      <div id="customer_details"></div>
      <textarea name="order_comments"></textarea>
    </form>
  </div>
  <div class="et_pb_wc_checkout_order_details">
    <form name="checkout" class="checkout woocommerce-checkout">
      <div id="order_review"></div>
      <input type="radio" name="shipping_method[0]" value="flat_rate:1" checked />
    </form>
  </div>
  <div class="et_pb_wc_checkout_payment_info">
    <form name="checkout" class="checkout woocommerce-checkout">
      <div id="order_review"></div>
      <button type="submit" id="place_order" name="woocommerce_checkout_place_order">Place order</button>
    </form>
  </div>
`;

const getFormData = () => getClassicCheckoutConfig().config.getFormData();

describe('getClassicCheckoutConfig - getFormData', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('reads a single checkout form unchanged', () => {
    document.body.innerHTML = SINGLE_FORM;

    const data = getFormData();

    expect(data['billing_first_name']).toBe('Jane');
    expect(data['shipping_method[0]']).toBe('flat_rate:1');
  });

  it('merges data across all Divi checkout forms so shipping_method is found', () => {
    document.body.innerHTML = DIVI_FORMS;

    const data = getFormData();

    // shipping_method lives in the order-details form, billing in the billing form
    expect(data['shipping_method[0]']).toBe('flat_rate:1');
    expect(data['billing_first_name']).toBe('Jane');
  });
});
