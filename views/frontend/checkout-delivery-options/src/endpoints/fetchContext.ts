import {useDeliveryOptionsStore} from '../store';
import {useSettingsStore} from '@myparcel-woocommerce/frontend-common';

/**
 * Fetch and update the delivery options config. For use with changing shipping methods, for example, as doing so
 *  changes the prices of delivery and any extra options.
 */
export const fetchContext = (): void => {
  const deliveryOptions = useDeliveryOptionsStore();
  const settings = useSettingsStore();

  const {baseUrl, endpoints} = settings.state.actions;

  const endpoint = endpoints.fetchCheckoutContext;

  void jQuery.ajax({
    type: 'GET',
    url: `${baseUrl}/${endpoint.path}`,
    data: endpoint.parameters,
    success(data) {
      deliveryOptions.set({config: JSON.parse(data).config});
    },
  });
};
