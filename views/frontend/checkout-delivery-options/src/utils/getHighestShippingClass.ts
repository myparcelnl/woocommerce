import {useCheckoutStore} from '@myparcel-dev/pdk-checkout';

export const getHighestShippingClass = (): undefined | string => {
  const checkout = useCheckoutStore();

  // Empty string is a valid value, so we need to check for undefined
  return checkout.state.context.settings.highestShippingClass || undefined;
};
