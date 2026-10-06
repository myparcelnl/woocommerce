import {beforeEach} from 'vitest';
import {type Select2Plugin} from 'select2';
import jQuery from 'jquery';

type Select2Data = {id: string | number; text: string; disabled?: boolean};

beforeEach(() => {
  // Stands in for select2 and selectWoo, which WooCommerce loads at runtime: renders the options it is given.
  const select2 = function (this: JQuery, options?: unknown) {
    const data = (options as {data?: Select2Data[]} | undefined)?.data;

    if (data) {
      this.empty().append(data.map(({id, text, disabled}) => jQuery('<option>', {value: id, text, disabled})));
    }

    return this;
  } as Select2Plugin;

  jQuery.fn.select2 = select2;
  jQuery.fn.selectWoo = select2;

  global.$ = jQuery;
  global.jQuery = jQuery;
});
