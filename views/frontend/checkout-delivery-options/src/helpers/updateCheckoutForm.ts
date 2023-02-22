import {useCheckoutStore} from '@myparcel-woocommerce/frontend-common';

export const updateCheckoutForm = (event: Event): void => {
  const formData = new FormData(event.currentTarget as HTMLFormElement);
  const data = Object.fromEntries(formData.entries());

  const checkout = useCheckoutStore();

  checkout.set({form: data as Record<string, string | undefined>});
};
