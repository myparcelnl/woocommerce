import {FrontendAppContext} from '../types';
import {useSettingsStore} from '../store';

export const initializeSettingsStore = (context: FrontendAppContext['deliveryOptions']): void => {
  const settings = useSettingsStore();

  settings.set(context.settings);
};
