import {SELECTOR_FORM_CLASSIC} from '../constants';

export const isClassicCheckout = (): boolean => {
  return document.querySelector(SELECTOR_FORM_CLASSIC) !== null;
};
