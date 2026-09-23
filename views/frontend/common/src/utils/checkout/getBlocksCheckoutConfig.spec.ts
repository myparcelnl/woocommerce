// @vitest-environment happy-dom
import {beforeEach, describe, expect, it, vi} from 'vitest';
import {getBlocksCheckoutConfig} from './getBlocksCheckoutConfig';

// vi.mock is hoisted above the imports by Vitest, so the spies must be hoisted with it.
const {updateContextMock, refreshContextMock} = vi.hoisted(() => ({
  updateContextMock: vi.fn(async () => undefined),
  refreshContextMock: vi.fn(async () => undefined),
}));

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
  PdkField: {AddressType: 'addressType', IsBusiness: 'isBusiness', ShippingMethod: 'shippingMethod'},
}));

vi.mock('@myparcel-dev/pdk-checkout', () => ({
  PdkUtil: {GetElement: 'getElement'},
  SeparateAddressField: {Street: 'street', Number: 'number', NumberSuffix: 'numberSuffix'},
  useUtil: () => (selector: string) => document.querySelector(selector),
  updateContext: updateContextMock,
  refreshContextIfBusinessChanged: refreshContextMock,
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

/** Rebuilt per test, so a test can drop a selector an older WooCommerce Blocks does not have. */
let cartSelectors: Record<string, unknown>;

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

const createCartSelectors = (): Record<string, unknown> => ({
  getCustomerData: () => ({billingAddress: shippingAddress(), shippingAddress: shippingAddress()}),
  // eslint-disable-next-line @typescript-eslint/naming-convention
  getShippingRates: () => [{shipping_rates: [{rate_id: cart.rateId, selected: true}]}],
  isCustomerDataUpdating: () => cart.saving,
});

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

/** Register the config's form listener. */
const listen = (): void => {
  getBlocksCheckoutConfig().config.formChange?.(vi.fn());
};

beforeEach(() => {
  updateContextMock.mockClear();
  refreshContextMock.mockClear();

  cart.company = '';
  cart.saving = false;
  cart.rateId = 'flat_rate:1';

  cartSelectors = createCartSelectors();

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
  it('reports the recipient as a business when a company is filled in', () => {
    cart.company = 'MyParcel';

    expect(getBlocksCheckoutConfig().config.getFormData?.().isBusiness).toBe('1');
  });

  it('reports the recipient as private without a company', () => {
    expect(getBlocksCheckoutConfig().config.getFormData?.().isBusiness).toBe('');
  });

  it('asks for a fresh context once the customer data has been saved', async () => {
    listen();

    cart.company = 'MyParcel';
    await saveCustomerData();

    expect(refreshContextMock).toHaveBeenCalledTimes(1);
  });

  it('does not ask while the customer data is still being saved', async () => {
    listen();

    cart.company = 'MyParcel';
    cart.saving = true;
    await tick();

    expect(refreshContextMock).not.toHaveBeenCalled();
  });

  it('asks after every save, so an answer that came back too early is corrected', async () => {
    listen();

    cart.company = 'MyParcel';
    await saveCustomerData();
    await saveCustomerData();

    expect(refreshContextMock).toHaveBeenCalledTimes(2);
  });

  it('does not ask when the store cannot report saving', async () => {
    delete cartSelectors.isCustomerDataUpdating;
    listen();

    cart.company = 'MyParcel';
    await saveCustomerData();

    expect(refreshContextMock).not.toHaveBeenCalled();
  });

  it('fetches a new context when the shipping method changes', async () => {
    listen();

    cart.rateId = 'local_pickup:2';
    await tick();

    expect(updateContextMock).toHaveBeenCalledTimes(1);
  });

  it('keeps fetching on a shipping method change when the store cannot report saving', async () => {
    delete cartSelectors.isCustomerDataUpdating;
    listen();

    cart.rateId = 'local_pickup:2';
    await tick();

    expect(updateContextMock).toHaveBeenCalledTimes(1);
  });
});
