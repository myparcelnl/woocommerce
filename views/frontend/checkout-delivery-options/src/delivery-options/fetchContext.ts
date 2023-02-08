import {useDeliveryOptionsStore, useSettingsStore} from '@myparcel-woocommerce/frontend-common';

/**
 * Fetch and update the delivery options config. For use with changing shipping methods, for example, as doing so
 *  changes the prices of delivery and any extra options.
 */
export const fetchContext = (): void => {
  const deliveryOptions = useDeliveryOptionsStore();
  const settings = useSettingsStore();

  void jQuery.ajax({
    type: 'GET',
    url: settings.state.ajaxUrl,
    data: {
      action: settings.state.ajaxHookFetchContext,
      // shippingMethod: checkout.state.shippingMethod,
    },
    success(data) {
      deliveryOptions.set({config: JSON.parse(data).config});
    },
  });
};
