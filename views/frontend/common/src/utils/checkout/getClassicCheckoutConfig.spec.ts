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

const getForm = () => getClassicCheckoutConfig().config.getForm();

describe('getClassicCheckoutConfig - getForm', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('returns the single checkout form when there is only one', () => {
    document.body.innerHTML = SINGLE_FORM;

    expect(getForm().querySelector('#place_order')).not.toBeNull();
  });

  it('returns the form containing #place_order (the submit form) on Divi', () => {
    document.body.innerHTML = DIVI_FORMS;

    const form = getForm();

    // The submit form is inside the payment-info module, not the first (billing) form.
    expect(form.closest('.et_pb_wc_checkout_payment_info')).not.toBeNull();
    expect(form.querySelector('#place_order')).not.toBeNull();
  });

  it('falls back to the first checkout form when no form has a submit control', () => {
    document.body.innerHTML = `
      <form name="checkout" class="checkout"><input name="billing_first_name" value="A" /></form>
      <form name="checkout" class="checkout"><input name="billing_last_name" value="B" /></form>
    `;

    const form = getForm();

    // No #place_order anywhere → first form wins.
    expect(form.querySelector('input[name="billing_first_name"]')).not.toBeNull();
  });
});

describe('getClassicCheckoutConfig - formChange', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    // Minimal jQuery stub: jQuery(el).on('change', h) -> el.addEventListener('change', h).
    (globalThis as unknown as {jQuery: unknown}).jQuery = (el: EventTarget) => ({
      on: (event: string, handler: EventListener) => el.addEventListener(event, handler),
    });
  });

  const fireChange = (selector: string) => {
    const el = document.querySelector(selector)!;
    el.dispatchEvent(new Event('change', {bubbles: true}));
  };

  it('fires the callback on change in the single form', () => {
    document.body.innerHTML = SINGLE_FORM;
    let calls = 0;

    getClassicCheckoutConfig().config.formChange(() => {
      calls += 1;
    });
    fireChange('input[name="shipping_method[0]"]');

    expect(calls).toBe(1);
  });

  it('fires the callback on change in ANY Divi checkout form', () => {
    document.body.innerHTML = DIVI_FORMS;
    let calls = 0;

    getClassicCheckoutConfig().config.formChange(() => {
      calls += 1;
    });

    // Shipping method lives in the order-details form...
    fireChange('.et_pb_wc_checkout_order_details input[name="shipping_method[0]"]');
    // ...billing field lives in a different form.
    fireChange('.et_pb_wc_checkout_billing input[name="billing_first_name"]');

    expect(calls).toBe(2);
  });
});
