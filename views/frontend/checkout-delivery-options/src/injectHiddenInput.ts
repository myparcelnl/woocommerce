import {useSettingsStore} from './store';

/**
 * Create an input field in the checkout form to be able to pass the checkout data to the $_POST variable when
 * placing the order.
 */
export const injectHiddenInput = (): void => {
  const settings = useSettingsStore();

  const hiddenInput = document.createElement('input');
  hiddenInput.setAttribute('type', 'hidden');
  hiddenInput.setAttribute('name', settings.hiddenInputName);

  const form: HTMLFormElement | null = document.querySelector('form[name="checkout"]');

  form?.appendChild(hiddenInput);
};
