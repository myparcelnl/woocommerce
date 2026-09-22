// @vitest-environment happy-dom
import {beforeEach, describe, expect, it, vi} from 'vitest';
import {getBlocksCheckoutConfig} from './getBlocksCheckoutConfig';

const {updateContext, getShippingRate, selectors} = vi.hoisted(() => ({
  updateContext: vi.fn().mockResolvedValue(undefined),
  getShippingRate: vi.fn(),
  selectors: {
    getCustomerData: vi.fn(),
    getCartData: vi.fn(),
    isItemPendingQuantity: vi.fn(),
    isItemPendingDelete: vi.fn(),
    isCustomerDataUpdating: vi.fn(),
    isShippingRateBeingSelected: vi.fn(),
  },
}));

vi.mock('@myparcel-dev/pdk-checkout-common', () => ({
  AddressType: {Billing: 'billing', Shipping: 'shipping'},
  AddressField: {},
  PdkField: {},
}));
vi.mock('@myparcel-dev/pdk-checkout', () => ({
  updateContext,
  SeparateAddressField: {},
  PdkUtil: {},
  useUtil: vi.fn(),
}));
vi.mock('./getShippingRate', () => ({getShippingRate}));
vi.mock('./useWcCartStore', () => ({useWcCartStore: () => ({selectors})}));

describe('Blocks cart context updates', () => {
  let notify: () => Promise<void>;
  let callback: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    vi.clearAllMocks();
    selectors.getCustomerData.mockReturnValue({shippingAddress: {country: 'NL'}});
    selectors.getCartData.mockReturnValue({items: [{key: 'variant-a', id: 100, quantity: 1}]});
    selectors.isItemPendingQuantity.mockReturnValue(false);
    selectors.isItemPendingDelete.mockReturnValue(false);
    selectors.isCustomerDataUpdating.mockReturnValue(false);
    selectors.isShippingRateBeingSelected.mockReturnValue(false);
    getShippingRate.mockReturnValue({rate_id: 'flat_rate:1'});
    vi.stubGlobal('wp', {
      data: {
        subscribe: (listener: typeof notify) => {
          notify = listener;
        },
      },
    });
    callback = vi.fn();
    getBlocksCheckoutConfig().config.formChange(callback);
  });

  it('does not fetch again for unrelated store changes or delivery-option fee updates', async () => {
    selectors.getCartData.mockReturnValue({items: [{key: 'variant-a', id: 100, quantity: 1}], totals: {total: 50}});
    await notify();
    expect(updateContext).not.toHaveBeenCalled();
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
  ])('refreshes after a saved %s change', async (_label, items) => {
    selectors.getCartData.mockReturnValue({items});
    await notify();
    await notify();
    expect(updateContext).toHaveBeenCalledTimes(1);
    expect(callback).toHaveBeenCalledTimes(1);
  });

  it.each(['isItemPendingQuantity', 'isItemPendingDelete'] as const)(
    'waits until %s has completed',
    async (selector) => {
      selectors.getCartData.mockReturnValue({items: [{key: 'variant-a', id: 100, quantity: 2}]});
      selectors[selector].mockReturnValue(true);
      await notify();
      expect(updateContext).not.toHaveBeenCalled();
      expect(selectors[selector]).toHaveBeenCalledWith('variant-a');
      selectors[selector].mockReturnValue(false);
      await notify();
      expect(updateContext).toHaveBeenCalledTimes(1);
    },
  );

  it('waits for a removed item using selectors available in older WooCommerce Blocks', async () => {
    selectors.getCartData.mockReturnValue({items: []});
    selectors.isItemPendingDelete.mockReturnValue(true);

    await notify();
    expect(selectors.isItemPendingDelete).toHaveBeenCalledWith('variant-a');
    expect(updateContext).not.toHaveBeenCalled();

    selectors.isItemPendingDelete.mockReturnValue(false);
    await notify();
    expect(updateContext).toHaveBeenCalledTimes(1);
    expect(callback).toHaveBeenCalledTimes(1);
  });

  it('reads the selected shipping method before fetching its context', async () => {
    getShippingRate.mockReturnValue({rate_id: 'flat_rate:2'});
    selectors.isShippingRateBeingSelected.mockReturnValue(true);
    await notify();
    expect(updateContext).not.toHaveBeenCalled();
    selectors.isShippingRateBeingSelected.mockReturnValue(false);
    await notify();
    expect(callback.mock.invocationCallOrder[0]).toBeLessThan(updateContext.mock.invocationCallOrder[0]);
  });

  it('keeps address updates connected to the existing form listener', async () => {
    selectors.getCustomerData.mockReturnValue({shippingAddress: {country: 'BE'}});
    await notify();
    expect(callback).toHaveBeenCalledTimes(1);
    expect(updateContext).not.toHaveBeenCalled();
  });

  it('keeps form updates working when the context request fails', async () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
    const error = new TypeError('Failed to fetch');
    updateContext.mockRejectedValueOnce(error);
    selectors.getCartData.mockReturnValue({items: [{key: 'variant-a', id: 100, quantity: 2}]});

    await expect(notify()).resolves.toBeUndefined();
    expect(callback).toHaveBeenCalledTimes(1);
    expect(warn).toHaveBeenCalledWith('[woocommerce-myparcel] delivery-options context update failed', error);

    selectors.getCartData.mockReturnValue({items: [{key: 'variant-a', id: 100, quantity: 3}]});
    await notify();
    expect(callback).toHaveBeenCalledTimes(2);
    expect(updateContext).toHaveBeenCalledTimes(2);
    warn.mockRestore();
  });
});
