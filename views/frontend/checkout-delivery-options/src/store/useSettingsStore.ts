import {FrontendSettings} from '@myparcel-woocommerce/frontend-checkout-delivery-options';
import {createStore} from './createStore';

export const useSettingsStore = createStore<FrontendSettings>('settings', () => {
  return {
    state: {} as FrontendSettings,
  };
});
