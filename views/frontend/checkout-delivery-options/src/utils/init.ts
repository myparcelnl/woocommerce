import {
  PdkDeliveryOptionsEvent,
  initializeCheckoutDeliveryOptions as initialize,
  useEvent,
  useSettings,
  getPackageTypeFromShippingMethod,
  defaultGetPackageType,
  defaultUpdateDeliveryOptions,
} from '@myparcel-dev/pdk-checkout';
import {FrontendEndpoint} from '@myparcel-dev/pdk-common';
import {getHighestShippingClass} from './getHighestShippingClass';

/**
 * Build the fully qualified URL the delivery-options widget should POST to when
 * it calls the capabilities proxy. Mirrors how the address-widget derives its
 * URL from `useSettings().actions.baseUrl` + `endpoint.parameters`.
 */
const getProxyCapabilitiesUrl = (): string | undefined => {
  const settings = useSettings();
  const endpoint = settings.actions.endpoints[FrontendEndpoint.ProxyCapabilities];

  if (!endpoint?.parameters || Object.keys(endpoint.parameters).length === 0) {
    // eslint-disable-next-line no-console
    console.warn(
      `[woocommerce-myparcel] Missing or empty '${FrontendEndpoint.ProxyCapabilities}' endpoint in checkout context`,
    );
    return undefined;
  }

  const query = new URLSearchParams(endpoint.parameters).toString();

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
