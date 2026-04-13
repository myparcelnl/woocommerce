// @vitest-environment happy-dom
import {beforeEach, describe, expect, it, vi, type MockInstance} from 'vitest';

const initialize = vi.fn();
const useSettings = vi.fn();
const defaultUpdateDeliveryOptions = vi.fn();
const defaultGetPackageType = vi.fn(() => undefined);
const getPackageTypeFromShippingMethod = vi.fn((shippingClass: string) => shippingClass);
const useEvent = vi.fn((event: string) => event);

vi.mock('@myparcel-dev/pdk-checkout', () => ({
  PdkDeliveryOptionsEvent: {DeliveryOptionsUpdated: 'deliveryOptionsUpdated'},
  initializeCheckoutDeliveryOptions: initialize,
  useEvent,
  useSettings,
  getPackageTypeFromShippingMethod,
  defaultGetPackageType,
  defaultUpdateDeliveryOptions,
}));

vi.mock('@myparcel-dev/pdk-common', () => ({
  FrontendEndpoint: {ProxyCapabilities: 'proxyCapabilities'},
}));

vi.mock('./getHighestShippingClass', () => ({
  getHighestShippingClass: vi.fn(() => undefined),
}));

const BASE_URL = 'https://shop.test/wp-json/myparcelcom/frontend/v1/myparcelcom';

const settingsWith = (endpoints: Record<string, unknown>) => ({
  actions: {baseUrl: BASE_URL, endpoints},
});

const getInitArgs = () => {
  expect(initialize).toHaveBeenCalledTimes(1);
  return initialize.mock.calls[0][0] as {
    getPackageType: () => string | undefined;
    updateDeliveryOptions: (state: unknown) => Record<string, unknown>;
  };
};

describe('initializeCheckoutDeliveryOptions', () => {
  let warnSpy: MockInstance<Parameters<Console['warn']>, ReturnType<Console['warn']>>;

  beforeEach(() => {
    vi.clearAllMocks();
    defaultUpdateDeliveryOptions.mockImplementation(() => ({packageType: 'package', carrier: 'postnl'}));
    warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
  });

  it('composes the proxyCapabilities URL from baseUrl + endpoint.parameters', async () => {
    useSettings.mockReturnValue(
      settingsWith({proxyCapabilities: {parameters: {action: 'proxyCapabilities'}}}),
    );

    const {initializeCheckoutDeliveryOptions} = await import('./init');
    initializeCheckoutDeliveryOptions();

    const config = getInitArgs().updateDeliveryOptions({});

    expect(config.proxyCapabilities).toBe(`${BASE_URL}?action=proxyCapabilities`);
    expect(config.packageType).toBe('package');
    expect(config.carrier).toBe('postnl');
  });

  it('preserves every entry from endpoint.parameters in the query string', async () => {
    useSettings.mockReturnValue(
      settingsWith({
        proxyCapabilities: {parameters: {action: 'proxyCapabilities', nonce: 'abc 123'}},
      }),
    );

    const {initializeCheckoutDeliveryOptions} = await import('./init');
    initializeCheckoutDeliveryOptions();

    const url = String(getInitArgs().updateDeliveryOptions({}).proxyCapabilities);
    const query = new URL(url).searchParams;

    expect(query.get('action')).toBe('proxyCapabilities');
    expect(query.get('nonce')).toBe('abc 123');
  });

  it('always exposes a proxyCapabilities key on the resulting config (sentinel)', async () => {
    useSettings.mockReturnValue(
      settingsWith({proxyCapabilities: {parameters: {action: 'proxyCapabilities'}}}),
    );

    const {initializeCheckoutDeliveryOptions} = await import('./init');
    initializeCheckoutDeliveryOptions();

    const config = getInitArgs().updateDeliveryOptions({});

    expect(Object.keys(config)).toContain('proxyCapabilities');
  });

  it('degrades gracefully when the proxyCapabilities endpoint is missing', async () => {
    useSettings.mockReturnValue(settingsWith({}));

    const {initializeCheckoutDeliveryOptions} = await import('./init');
    initializeCheckoutDeliveryOptions();

    const config = getInitArgs().updateDeliveryOptions({});

    expect(Object.keys(config)).not.toContain('proxyCapabilities');
    expect(warnSpy).toHaveBeenCalledTimes(1);
    expect(warnSpy.mock.calls[0][0]).toContain('proxyCapabilities');
  });

  it('degrades gracefully when the endpoint has no parameters key', async () => {
    useSettings.mockReturnValue(settingsWith({proxyCapabilities: {}}));

    const {initializeCheckoutDeliveryOptions} = await import('./init');
    initializeCheckoutDeliveryOptions();

    const config = getInitArgs().updateDeliveryOptions({});

    expect(Object.keys(config)).not.toContain('proxyCapabilities');
    expect(warnSpy).toHaveBeenCalledTimes(1);
    expect(warnSpy.mock.calls[0][0]).toContain('proxyCapabilities');
  });

  it('degrades gracefully when endpoint.parameters is empty', async () => {
    useSettings.mockReturnValue(settingsWith({proxyCapabilities: {parameters: {}}}));

    const {initializeCheckoutDeliveryOptions} = await import('./init');
    initializeCheckoutDeliveryOptions();

    const config = getInitArgs().updateDeliveryOptions({});

    expect(Object.keys(config)).not.toContain('proxyCapabilities');
    expect(warnSpy).toHaveBeenCalledTimes(1);
    expect(warnSpy.mock.calls[0][0]).toContain('proxyCapabilities');
  });

  it('hands the PDK both a getPackageType and an updateDeliveryOptions callback', async () => {
    useSettings.mockReturnValue(
      settingsWith({proxyCapabilities: {parameters: {action: 'proxyCapabilities'}}}),
    );

    const {initializeCheckoutDeliveryOptions} = await import('./init');
    initializeCheckoutDeliveryOptions();

    const args = getInitArgs();

    expect(typeof args.getPackageType).toBe('function');
    expect(typeof args.updateDeliveryOptions).toBe('function');
  });
});
