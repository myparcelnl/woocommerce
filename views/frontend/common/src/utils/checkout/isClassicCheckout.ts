export const isClassicCheckout = (): boolean => {
  return document.querySelector('form[name="checkout"]') !== null;
};
