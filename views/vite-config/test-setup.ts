import {vi, beforeEach} from 'vitest';
import {type Select2Plugin} from 'select2';
import jQuery from 'jquery';

beforeEach(() => {
  const jQueryMock = vi.fn(() => {
    const [jqueryInstance] = jQuery(global.window);

    const select2 = ((...args: unknown[]) => {
      return jqueryInstance.$(...args);
    }) as Select2Plugin;

    Object.defineProperty(jqueryInstance, 'select2', select2);
    Object.defineProperty(jqueryInstance, 'selectWoo', select2);

    return jqueryInstance;
  });

  global.$ = jQueryMock;
  global.jQuery = jQueryMock;
});
