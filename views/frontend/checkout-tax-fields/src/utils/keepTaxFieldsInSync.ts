export const keepTaxFieldsInSync = (): void => {
  const billingEori = document.getElementById('billing_eori') as HTMLInputElement;
  billingEori?.addEventListener('keyup', () => {
    const shippingEori = document.getElementById('shipping_eori') as HTMLInputElement;
    shippingEori.value = billingEori.value;
  });
};
