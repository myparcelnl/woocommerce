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
  PdkField: {AddressType: 'addressType', ShippingMethod: 'shippingMethod'},
  ADDRESS_FIELD_IS_BUSINESS: 'isBusiness',
}));

vi.mock('@myparcel-dev/pdk-checkout', () => ({
  PdkUtil: {GetElement: 'getElement'},
  SeparateAddressField: {Street: 'street', Number: 'number', NumberSuffix: 'numberSuffix'},
  useUtil: () => (selector: string) => document.querySelector(selector),
  updateContext: updateContextMock,
  refreshContextIfBusinessChanged: refreshContextMock,
}));

type CartItem = {key: string; id: number; quantity: number};

/** Everything the fake cart store needs to answer the selectors the config calls. */
const cart = {
  company: '',
  /** True while WooCommerce is saving the customer data to the server. */
  saving: false,
  rateId: 'flat_rate:1',
  /** True while WooCommerce is saving a new shipping method selection. */
  selectingRate: false,
  items: [] as CartItem[],
  /** Other cart data that changes without a change to the items, for example a fee. */
  totals: {total: 0},
  /** Keys of the items that WooCommerce is still saving a new quantity for. */
  pendingQuantity: [] as string[],
  /** Keys of the items that WooCommerce is still deleting on the server. */
  pendingDelete: [] as string[],
};

const DEFAULT_ITEMS: CartItem[] = [{key: 'variant-a', id: 100, quantity: 1}];

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
  getCartData: () => ({items: cart.items, totals: cart.totals}),
  isCustomerDataUpdating: () => cart.saving,
  isItemPendingQuantity: (key: string) => cart.pendingQuantity.includes(key),
  isItemPendingDelete: (key: string) => cart.pendingDelete.includes(key),
  isShippingRateBeingSelected: () => cart.selectingRate,
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

/** Register the config's form listener and return the callback it calls on a form change. */
const listen = (): ReturnType<typeof vi.fn> => {
  const callback = vi.fn();

  getBlocksCheckoutConfig().config.formChange?.(callback);

  return callback;
};

beforeEach(() => {
  updateContextMock.mockClear();
  refreshContextMock.mockClear();

  cart.company = '';
  cart.saving = false;
  cart.rateId = 'flat_rate:1';
  cart.selectingRate = false;
  cart.items = [...DEFAULT_ITEMS];
  cart.totals = {total: 0};
  cart.pendingQuantity = [];
  cart.pendingDelete = [];

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
  it('reports a business recipient on the address the company belongs to', () => {
    cart.company = 'MyParcel';

    const formData = getBlocksCheckoutConfig().config.getFormData?.();

    expect(formData?.['shipping-isBusiness']).toBe('1');
    expect(formData?.['billing-isBusiness']).toBe('1');
  });

  it('reports the recipient as private without a company', () => {
    const formData = getBlocksCheckoutConfig().config.getFormData?.();

    expect(formData?.['shipping-isBusiness']).toBe('');
    expect(formData?.['billing-isBusiness']).toBe('');
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

  it('does not fetch again when other cart data changes, for example a delivery options fee', async () => {
    const callback = listen();

    cart.totals = {total: 50};
    await tick();

    expect(updateContextMock).not.toHaveBeenCalled();
    expect(callback).not.toHaveBeenCalled();
  });

  it.each([
    ['quantity', [{key: 'variant-a', id: 100, quantity: 2}]],
    ['variation', [{key: 'variant-b', id: 101, quantity: 1}]],
    [
      'added item',
      [
        {key: 'variant-a', id: 100, quantity: 1},
        {key: 'unknown', id: 200, quantity: 1},
      ],
    ],
    ['removed item', []],
  ])('fetches a new context once after a saved %s change', async (_label, items) => {
    const callback = listen();

    cart.items = items;
    await tick();
    await tick();

    expect(updateContextMock).toHaveBeenCalledTimes(1);
    expect(callback).toHaveBeenCalledTimes(1);
  });

  it.each(['pendingQuantity', 'pendingDelete'] as const)(
    'waits until the server has saved the item (%s)',
    async (pending) => {
      listen();

      cart.items = [{key: 'variant-a', id: 100, quantity: 2}];
      cart[pending] = ['variant-a'];
      await tick();

      expect(updateContextMock).not.toHaveBeenCalled();

      cart[pending] = [];
      await tick();

      expect(updateContextMock).toHaveBeenCalledTimes(1);
    },
  );

  it('waits until the server has deleted an item that is no longer in the cart data', async () => {
    const callback = listen();

    cart.items = [];
    cart.pendingDelete = ['variant-a'];
    await tick();

    expect(updateContextMock).not.toHaveBeenCalled();

    cart.pendingDelete = [];
    await tick();

    expect(updateContextMock).toHaveBeenCalledTimes(1);
    expect(callback).toHaveBeenCalledTimes(1);
  });

  it('reads the selected shipping method before it fetches the context for it', async () => {
    const callback = listen();

    cart.rateId = 'flat_rate:2';
    cart.selectingRate = true;
    await tick();

    expect(updateContextMock).not.toHaveBeenCalled();

    cart.selectingRate = false;
    await tick();

    expect(callback.mock.invocationCallOrder[0]).toBeLessThan(updateContextMock.mock.invocationCallOrder[0]);
  });

  it('calls the form listener on an address change without fetching a new context', async () => {
    const callback = listen();

    cart.company = 'MyParcel';
    await tick();

    expect(callback).toHaveBeenCalledTimes(1);
    expect(updateContextMock).not.toHaveBeenCalled();
  });

  it('keeps calling the form listener when the context request fails', async () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
    const error = new TypeError('Failed to fetch');
    const callback = listen();

    updateContextMock.mockRejectedValueOnce(error);
    cart.items = [{key: 'variant-a', id: 100, quantity: 2}];

    await expect(tick()).resolves.toBeUndefined();
    expect(callback).toHaveBeenCalledTimes(1);
    expect(warn).toHaveBeenCalledWith('[woocommerce-myparcel] delivery-options context update failed', error);

    cart.items = [{key: 'variant-a', id: 100, quantity: 3}];
    await tick();

    expect(callback).toHaveBeenCalledTimes(2);
    expect(updateContextMock).toHaveBeenCalledTimes(2);
    warn.mockRestore();
  });
});
