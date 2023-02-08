/**
 * Trigger an event on a given element. Defaults to body.
 */
export const triggerEvent = (
  eventName: string,
  detail: unknown = null,
  element: string | HTMLElement | Document = document,
): void => {
  let eventSource: HTMLElement | Document;

  if (typeof element === 'string') {
    eventSource = document.querySelector(element) as HTMLElement;
  } else {
    eventSource = element;
  }

  if (detail) {
    eventSource?.dispatchEvent(new CustomEvent(eventName, {detail}));
    return;
  }

  eventSource?.dispatchEvent(new Event(eventName));
};
