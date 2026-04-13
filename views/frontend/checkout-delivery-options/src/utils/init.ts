import {
  PdkDeliveryOptionsEvent,
  initializeCheckoutDeliveryOptions as initialize,
  useEvent,
  useSettings,
  getPackageTypeFromShippingMethod,
  defaultGetPackageType,
  defaultUpdateDeliveryOptions,
} from '@myparcel-dev/pdk-checkout';
import {getHighestShippingClass} from './getHighestShippingClass';

/**
 * Action key for the capabilities proxy endpoint exposed by PDK on
 * `settings.actions.endpoints`. Kept as a local constant because `@myparcel-dev/pdk-common`
 * does not yet have a `FrontendEndpoint.ProxyCapabilities` enum entry.
 */
const PROXY_CAPABILITIES = 'proxyCapabilities';

/**
 * Build the fully qualified URL the delivery-options widget should POST to when
 * it calls the capabilities proxy. Mirrors how the address-widget derives its
 * URL from `useSettings().actions.baseUrl` + `endpoint.parameters`.
 */
const getProxyCapabilitiesUrl = (): string | undefined => {
  const settings = useSettings();
  const endpoints = settings.actions.endpoints as Record<
    string,
    {parameters?: Record<string, unknown>} | undefined
  >;
  const endpoint = endpoints[PROXY_CAPABILITIES];

  if (!endpoint) {
    // eslint-disable-next-line no-console
    console.warn(`[woocommerce-myparcel] Missing '${PROXY_CAPABILITIES}' endpoint in checkout context`);
    return undefined;
  }

  const query = new URLSearchParams(
    Object.entries(endpoint.parameters ?? {}).map(([key, value]) => [key, String(value)]),
  ).toString();

  const {baseUrl} = settings.actions;

  return query ? `${baseUrl}?${query}` : baseUrl;
};

/**
 * Callback to initialize the checkout delivery-options view, called by the PDK
 * through `usePdkCheckout().onInitialize(...)`.
 */
export const initializeCheckoutDeliveryOptions = (): void => {
  const proxyCapabilities = getProxyCapabilitiesUrl();

  initialize({
    getPackageType() {
      const shippingClass = getHighestShippingClass();

      return shippingClass ? getPackageTypeFromShippingMethod(shippingClass) : defaultGetPackageType();
    },
    updateDeliveryOptions(state) {
      const baseConfig = defaultUpdateDeliveryOptions(state);

      return proxyCapabilities ? {...baseConfig, proxyCapabilities} : baseConfig;
    },
  });

  document.addEventListener(useEvent(PdkDeliveryOptionsEvent.DeliveryOptionsUpdated), () => {
    jQuery(document.body).trigger('update_checkout');
  });
};
