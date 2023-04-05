/**
 * Initialize WooCommerce tooltips ("tiptips").
 */
export const initializeTooltips = (): void => {
  document.body.dispatchEvent(new Event('init_tooltips'));
};
