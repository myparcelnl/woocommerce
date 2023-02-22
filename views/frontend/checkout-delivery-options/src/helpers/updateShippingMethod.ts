import {useCheckoutStore} from '@myparcel-woocommerce/frontend-common';

/**
 * Update the shipping method to the new selections. Triggers hiding/showing of the delivery options.
 */
export const updateShippingMethod = (): void => {
  const checkout = useCheckoutStore();

  const shippingMethod = checkout.state.form[`shipping_method[0]`];

  console.log(shippingMethod, checkout.state.form);

  /**
   * This shipping method will have a suffix in the checkout, but this is not present in the array of
   *  selected shipping methods from the SETTING_DELIVERY_OPTIONS_DISPLAY setting.
   *
   * All variants of flat_rate (including shipping classes) do already have their suffix set properly.
   */
  // todo check if this is needed
  // if (shippingMethod?.startsWith('flat_rate')) {
  //   // const shippingClass = getHighestShippingClass();
  //   const shippingClass = '1';
  //
  //   if (shippingClass) {
  //     shippingMethod = `flat_rate:${shippingClass}`;
  //   }
  // }

  checkout.set({shippingMethod});
};
