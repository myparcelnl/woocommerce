/**
 * Get the current shipping method without the shipping class.
 *
 * @returns {String}
 */
export const getShippingMethodWithoutClass = (): string => {
  let shippingMethod = selectedShippingMethod;
  const indexOfSemicolon = shippingMethod.indexOf(':');

  shippingMethod = shippingMethod.substring(0, indexOfSemicolon === -1 ? shippingMethod.length : indexOfSemicolon);

  return shippingMethod;
};
