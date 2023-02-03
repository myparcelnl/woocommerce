export const getElement = <E extends Element = Element>(selector: string): null | E => {
  const element = document.querySelector<E>(selector);

  if (!element) {
    // eslint-disable-next-line no-console
    console.warn(`Element not found: "${selector}"`);
  }

  return element ?? null;
};
