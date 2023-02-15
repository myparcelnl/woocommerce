import {useCheckoutStore} from '../';

export const getFieldValue = (name: string): undefined | string => {
  const checkout = useCheckoutStore();

  return checkout.state.form[name]?.trim();
};
