import {useCheckoutStore} from '@myparcel-pdk/checkout';

export const getHighestShippingClass = (): undefined | string => {
  const checkout = useCheckoutStore();

  console.log(checkout.state.context.settings);
  // Empty string is a valid value, so we need to check for undefined
  return checkout.state.context.settings.highestShippingClass || undefined;
};
