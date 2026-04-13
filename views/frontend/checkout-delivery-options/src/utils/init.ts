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
 * TODO(pdk-common): remove the cast in getProxyCapabilitiesUrl once FrontendEndpoint.ProxyCapabilities ships.
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

  if (!endpoint?.parameters || Object.keys(endpoint.parameters).length === 0) {
    // eslint-disable-next-line no-console
    console.warn(
      `[woocommerce-myparcel] Missing or empty '${PROXY_CAPABILITIES}' endpoint in checkout context`,
    );
    return undefined;
  }

  const query = new URLSearchParams(
    Object.entries(endpoint.parameters).map(([key, value]) => [key, String(value)]),
  ).toString();

  return `${settings.actions.baseUrl}?${query}`;
};

/**
 * Callback to initialize the checkout delivery-options view, called by the PDK
 * through `usePdkCheckout().onInitialize(...)`.
 */
export const initializeCheckoutDeliveryOptions = (): void => {
  initialize({
    getPackageType() {
      const shippingClass = getHighestShippingClass();

      return shippingClass ? getPackageTypeFromShippingMethod(shippingClass) : defaultGetPackageType();
    },
    updateDeliveryOptions(state) {
      const baseConfig = defaultUpdateDeliveryOptions(state);
      const proxyCapabilities = getProxyCapabilitiesUrl();

      return proxyCapabilities ? {...baseConfig, proxyCapabilities} : baseConfig;
    },
  });

  document.addEventListener(useEvent(PdkDeliveryOptionsEvent.DeliveryOptionsUpdated), () => {
    jQuery(document.body).trigger('update_checkout');
  });
};
