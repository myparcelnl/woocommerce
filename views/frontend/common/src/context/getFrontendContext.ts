import {FrontendAppContext, getElement} from '../';

export const getFrontendContext = (): FrontendAppContext['checkout'] => {
  const wrapper = getElement('#mypa-delivery-options-wrapper');
  const context = wrapper?.getAttribute('data-context');

  if (!wrapper || !context) {
    throw new Error('No delivery options wrapper or context found.');
  }

  wrapper.removeAttribute('data-context');

  const {checkout} = JSON.parse(context) as FrontendAppContext;

  return checkout;
};
