/**
 * @returns {boolean}
 */
export const hasUnrenderedDeliveryOptions = () => {
  return Boolean(getElement('#myparcel-delivery-options'));
};

export const getElement = (selector: string): null | JQuery => {
  const element = jQuery(selector);
  return element.length ? element : null;
};
