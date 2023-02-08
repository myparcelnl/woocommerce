import {createStore} from './createStore';
import {FrontendSettings} from '../';

export const useSettingsStore = createStore<FrontendSettings>('settings', () => {
  return {
    state: {} as FrontendSettings,
  };
});
