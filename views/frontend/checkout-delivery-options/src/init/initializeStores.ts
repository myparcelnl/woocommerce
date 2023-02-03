import {useCheckoutStore, useSettingsStore} from '../store';
import {FrontendAppContext} from '@myparcel-woocommerce/frontend-checkout-delivery-options';
import {getAddress} from '../getAddress';
import {getAddressType} from '../getAddressType';
import {getElement} from '../dom/getElement';
import {useDeliveryOptionsStore} from '../store/useDeliveryOptionsStore';

export const initializeStores = (): void => {
  const settings = useSettingsStore();
  const deliveryOptions = useDeliveryOptionsStore();
  const checkout = useCheckoutStore();

  const wrapper = getElement('#mypa-delivery-options-wrapper');
  const context = wrapper?.getAttribute('data-context');

  if (!wrapper || !context) {
    throw new Error('No delivery options wrapper or context found.');
  }

  const {deliveryOptions: data} = JSON.parse(context) as FrontendAppContext;

  settings.set(data.settings);
  deliveryOptions.set({
    config: data.config,
    strings: data.strings,
    address: getAddress(),
  });

  checkout.set({
    addressType: getAddressType(),
  });
};
