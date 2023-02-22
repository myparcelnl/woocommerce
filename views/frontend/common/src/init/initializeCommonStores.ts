import {FrontendAppContext, initializeCheckoutStore, initializeSettingsStore} from '../';

export const initializeCommonStores = (context: FrontendAppContext['checkout']): void => {
  initializeSettingsStore(context);
  initializeCheckoutStore();
};
