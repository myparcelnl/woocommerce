// @vitest-environment happy-dom
import {beforeEach, describe, expect, it, vi} from 'vitest';
import {getBlocksCheckoutConfig} from './getBlocksCheckoutConfig';

// vi.mock is hoisted above the imports by Vitest, so the spy must be hoisted with it.
const {updateContextMock} = vi.hoisted(() => ({updateContextMock: vi.fn(async () => undefined)}));

// The config object references these enums as object keys only; simple stubs suffice.
vi.mock('@myparcel-dev/pdk-checkout-common', () => ({
  AddressType: {Billing: 'billing', Shipping: 'shipping'},
  AddressField: {
    Address1: 'address1',
    Address2: 'address2',
    City: 'city',
    Country: 'country',
    PostalCode: 'postalCode',
  },
  PdkField: {AddressType: 'addressType', ShippingMethod: 'shippingMethod'},
}));

vi.mock('@myparcel-dev/pdk-checkout', () => ({
  PdkUtil: {GetElement: 'getElement'},
  SeparateAddressField: {Street: 'street', Number: 'number', NumberSuffix: 'numberSuffix'},
  useUtil: () => (selector: string) => document.querySelector(selector),
  updateContext: updateContextMock,
}));

/** Everything the fake cart store needs to answer the selectors the config calls. */
const cart = {
  company: '',
  /** True while WooCommerce is saving the customer data to the server. */
  saving: false,
  rateId: 'flat_rate:1',
};

/** The subscriber the config registers through `wp.data.subscribe`. */
let subscriber: () => Promise<void> | void;

const shippingAddress = (): Record<string, string> => ({
  // eslint-disable-next-line @typescript-eslint/naming-convention
  address_1: 'Antareslaan 31',
  // eslint-disable-next-line @typescript-eslint/naming-convention
  address_2: '',
  city: 'Hoofddorp',
  company: cart.company,
  country: 'NL',
  postcode: '2132 JE',
});

const cartSelectors = {
  getCustomerData: () => ({billingAddress: shippingAddress(), shippingAddress: shippingAddress()}),
  // eslint-disable-next-line @typescript-eslint/naming-convention
  getShippingRates: () => [{shipping_rates: [{rate_id: cart.rateId, selected: true}]}],
  isCustomerDataUpdating: () => cart.saving,
};

/** Run one `wp.data` store tick. */
const tick = async (): Promise<void> => {
  await subscriber();
};

/** WooCommerce saving the changed customer data: a request starts, then finishes. */
const saveCustomerData = async (): Promise<void> => {
  cart.saving = true;
  await tick();
  cart.saving = false;
  await tick();
};

/** Register the config's form listener, with `company` as the starting value. */
const listen = (company = ''): void => {
  cart.company = company;
  getBlocksCheckoutConfig().config.formChange?.(vi.fn());
};

beforeEach(() => {
  updateContextMock.mockClear();

  cart.company = '';
  cart.saving = false;
  cart.rateId = 'flat_rate:1';

  window.wp = {
    data: {
      select: () => cartSelectors,
      dispatch: () => ({}),
      subscribe: (callback: () => Promise<void> | void) => {
        subscriber = callback;

        return () => undefined;
      },
    },
  } as unknown as typeof window.wp;

  window.wc = {wcBlocksData: {CART_STORE_KEY: 'wc/store/cart'}} as unknown as typeof window.wc;
});

describe('getBlocksCheckoutConfig', () => {
  it('does not fetch a new context while the company is still being saved', async () => {
    listen();

    cart.company = 'MyParcel';
    cart.saving = true;
    await tick();

    expect(updateContextMock).not.toHaveBeenCalled();
  });

  it('fetches a new context once the company has been saved', async () => {
    listen();

    cart.company = 'MyParcel';
    await saveCustomerData();

    expect(updateContextMock).toHaveBeenCalledTimes(1);
  });

  it('fetches a new context when the company is cleared', async () => {
    listen('MyParcel');

    cart.company = '';
    await saveCustomerData();

    expect(updateContextMock).toHaveBeenCalledTimes(1);
  });

  it('fetches a new context when the shipping method changes', async () => {
    listen();

    cart.rateId = 'local_pickup:2';
    await tick();

    expect(updateContextMock).toHaveBeenCalledTimes(1);
  });

  it('fetches a new context once when the shipping method changes as the company is saved', async () => {
    listen();

    cart.company = 'MyParcel';
    cart.saving = true;
    await tick();

    cart.saving = false;
    cart.rateId = 'local_pickup:2';
    await tick();

    expect(updateContextMock).toHaveBeenCalledTimes(1);
  });

  it('does not fetch a new context when the recipient stays a business', async () => {
    listen('MyParcel');

    cart.company = 'MyParcel B.V.';
    await saveCustomerData();

    expect(updateContextMock).not.toHaveBeenCalled();
  });
});
