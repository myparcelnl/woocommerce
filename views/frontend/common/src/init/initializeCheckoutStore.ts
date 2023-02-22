import {getAddressType, getSelectedShippingMethod, useCheckoutStore} from '../';

export const initializeCheckoutStore = (): void => {
  const checkout = useCheckoutStore();

  checkout.set({
    addressType: getAddressType(),
    shippingMethod: getSelectedShippingMethod(),
  });
};
