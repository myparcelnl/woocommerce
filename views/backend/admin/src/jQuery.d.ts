/// <reference types="@types/jquery" />
/// <reference types="@types/select2" />

import {Select2Plugin} from 'select2';

declare global {
  // eslint-disable-next-line @typescript-eslint/naming-convention
  interface JQuery<TElement = HTMLElement> {
    selectWoo: Select2Plugin<TElement>;
  }
}
