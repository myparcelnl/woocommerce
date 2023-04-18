export const getElement = <E extends Element = Element>(selector: string, warn = true): null | E => {
  const element = document.querySelector<E>(selector);

  if (!element && warn) {
    // eslint-disable-next-line no-console
    console.warn(`Element not found: "${selector}"`);
  }

  return element ?? null;
};
