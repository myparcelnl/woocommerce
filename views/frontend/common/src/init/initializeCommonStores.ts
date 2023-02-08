import {FrontendAppContext, initializeCheckoutStore, initializeSettingsStore} from '../';

export const initializeCommonStores = (context: FrontendAppContext['deliveryOptions']): void => {
  initializeSettingsStore(context);
  initializeCheckoutStore();
};
