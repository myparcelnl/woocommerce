/**
 * Trigger an event on a given element. Defaults to body.
 */
export const triggerEvent = (eventName: string, element: string | HTMLElement | Document = 'body'): void => {
  let eventSource: HTMLElement | Document;

  if (typeof element === 'string') {
    eventSource = document.querySelector(element) as HTMLElement;
  } else {
    eventSource = element;
  }

  eventSource?.dispatchEvent(new Event(eventName));
};
