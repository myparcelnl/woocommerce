/**
 * Create an input field in the checkout form to be able to pass the checkout data to the $_POST variable when
 * placing the order.
 *
 * @see includes/class-wcmp-checkout.php::save_delivery_options();
 */
export const injectHiddenInput = (): void => {
  const hiddenDataInput = document.createElement('input');
  hiddenDataInput.setAttribute('type', 'hidden');
  hiddenDataInput.setAttribute('name', window.MyParcelNLData.hiddenInputName);

  const form: HTMLFormElement | null = document.querySelector('form[name="checkout"]');

  form?.appendChild(hiddenDataInput);
};
