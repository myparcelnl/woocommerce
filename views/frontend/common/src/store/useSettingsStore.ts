import {FrontendSettings} from '../';
import {createStore} from './createStore';

export const useSettingsStore = createStore<FrontendSettings>('settings', () => {
  return {
    state: {
      allowedShippingMethods: [],
      disallowedShippingMethods: [],
    } as FrontendSettings,
  };
});
