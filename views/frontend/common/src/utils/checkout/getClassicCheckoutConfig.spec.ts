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

// happy-dom doesn't implement checkVisibility(); minimal display-only polyfill matching how
// getClassicCheckoutConfig uses it (no options: display:none on self or ancestor → not visible).
Element.prototype.checkVisibility ??= function (this: Element): boolean {
  for (let node: Element | null = this; node; node = node.parentElement) {
    if (window.getComputedStyle(node).display === 'none') {
      return false;
    }
  }

  return true;
};

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

/**
 * Divi 5 duplicate billing fieldset: a visible billing form + an additional-info form whose billing
 * input sits under a display:none ancestor (`.col-1`, not the input). `hiddenPostcode` models the
 * duplicate's value — empty (fresh checkout) or stale non-empty (returning customer).
 */
const DIVI_DUPLICATE_BILLING = (hiddenPostcode: string): string => `
  <div class="et_pb_wc_checkout_billing">
    <form name="checkout" class="checkout woocommerce-checkout">
      <input type="text" name="billing_postcode" value="1234AB" />
    </form>
  </div>
  <div class="et_pb_wc_checkout_additional_info">
    <form name="checkout" class="checkout woocommerce-checkout">
      <div class="col-1" style="display:none">
        <input type="text" name="billing_postcode" value="${hiddenPostcode}" />
      </div>
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

  it('ignores an empty hidden duplicate so the visible billing value survives (fresh checkout)', () => {
    document.body.innerHTML = DIVI_DUPLICATE_BILLING('');

    // The hidden duplicate is empty and comes later in DOM order; it must not clobber to ''.
    expect(getFormData()['billing_postcode']).toBe('1234AB');
  });

  it('ignores a stale hidden duplicate so the edited visible value survives (returning customer)', () => {
    document.body.innerHTML = DIVI_DUPLICATE_BILLING('9999ZZ');

    // The hidden duplicate carries a stale non-empty value; the visible edited value must win.
    expect(getFormData()['billing_postcode']).toBe('1234AB');
  });

  it('keeps a shipping method WooCommerce renders as a single hidden input', () => {
    // A single shipping method renders as <input type=hidden name=shipping_method[0]> — display:none
    // via the UA stylesheet (set explicitly here; happy-dom doesn't apply that rule). It carries a real
    // value, so it must NOT be dropped as a Divi container-hidden duplicate.
    document.body.innerHTML = `
      <form name="checkout" class="checkout woocommerce-checkout">
        <input type="text" name="billing_first_name" value="Jane" />
        <ul id="shipping_method">
          <li>
            <input type="hidden" name="shipping_method[0]" value="flat_rate:1" style="display:none" />
            <label>Flat rate</label>
          </li>
        </ul>
      </form>
    `;

    expect(getFormData()['shipping_method[0]']).toBe('flat_rate:1');
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

const getAddressType = (value = '') => getClassicCheckoutConfig().config.getAddressType(value);

describe('getClassicCheckoutConfig - getAddressType', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('returns Billing when there is no checkbox', () => {
    document.body.innerHTML = SINGLE_FORM;

    expect(getAddressType()).toBe('billing');
  });

  it('returns Billing when the checkbox is unchecked', () => {
    document.body.innerHTML = `
      <form name="checkout"><input type="checkbox" name="ship_to_different_address" value="1" /></form>
    `;

    expect(getAddressType()).toBe('billing');
  });

  it('returns Shipping when the checkbox is checked', () => {
    document.body.innerHTML = `
      <form name="checkout"><input type="checkbox" name="ship_to_different_address" value="1" checked /></form>
    `;

    expect(getAddressType()).toBe('shipping');
  });

  it('ignores a checked duplicate hidden by a display:none ancestor (Divi)', () => {
    document.body.innerHTML = `
      <div class="et_pb_wc_checkout_shipping">
        <form name="checkout"><input type="checkbox" name="ship_to_different_address" value="1" /></form>
      </div>
      <div class="et_pb_wc_checkout_additional_info">
        <form name="checkout">
          <div class="col-1" style="display:none">
            <input type="checkbox" name="ship_to_different_address" value="1" checked />
          </div>
        </form>
      </div>
    `;

    expect(getAddressType()).toBe('billing');
  });

  it('ignores a self-hidden checkbox that precedes the visible one and reads the visible one', () => {
    // Divi renders extra self-hidden ship_to_different_address inputs; an earlier one must not shadow
    // the visible checkbox the user actually toggles.
    document.body.innerHTML = `
      <div class="et_pb_wc_checkout_payment_info">
        <form name="checkout">
          <input type="checkbox" name="ship_to_different_address" value="1" style="display:none" />
        </form>
      </div>
      <div class="et_pb_wc_checkout_shipping">
        <form name="checkout"><input type="checkbox" name="ship_to_different_address" value="1" checked /></form>
      </div>
    `;

    expect(getAddressType()).toBe('shipping');
  });

  it('ignores the passed value and reads the live checkbox', () => {
    document.body.innerHTML = `
      <form name="checkout"><input type="checkbox" name="ship_to_different_address" value="1" /></form>
    `;

    // Passed '1' would map to Shipping under the old value-based mapping; the unchecked box wins.
    expect(getAddressType('1')).toBe('billing');
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

  it('fires the callback on WooCommerce\'s updated_checkout event', () => {
    // WooCommerce's AJAX re-render auto-selects the shipping method without a bubbling `change`;
    // only `updated_checkout` signals it, so formChange must listen to that too.
    document.body.innerHTML = SINGLE_FORM;
    let calls = 0;

    getClassicCheckoutConfig().config.formChange(() => {
      calls += 1;
    });
    document.body.dispatchEvent(new Event('updated_checkout', {bubbles: true}));

    expect(calls).toBe(1);
  });
});
