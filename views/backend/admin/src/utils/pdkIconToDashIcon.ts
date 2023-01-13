import {PdkIcon} from '@myparcel/pdk-frontend';
import {memoize} from 'lodash-es';

const PDK_DASH_ICON_MAP: Record<PdkIcon, string> = {
  [PdkIcon.ADD]: 'plus',
  [PdkIcon.ARROW_DOWN]: 'arrow-down-alt2',
  [PdkIcon.ARROW_LEFT]: 'arrow-left-alt2',
  [PdkIcon.ARROW_RIGHT]: 'arrow-right-alt2',
  [PdkIcon.ARROW_UP]: 'arrow-up-alt2',
  [PdkIcon.CLOSE]: 'no',
  [PdkIcon.DELETE]: 'trash',
  [PdkIcon.DOWNLOAD]: 'download',
  [PdkIcon.EDIT]: 'edit',
  [PdkIcon.EXPORT]: 'plus',
  [PdkIcon.EXTERNAL]: 'external',
  [PdkIcon.PRINT]: 'printer',
  [PdkIcon.REFRESH]: 'update',
  [PdkIcon.RETURN]: 'undo',
  [PdkIcon.SAVE]: 'yes',
};

export const pdkIconToDashIcon = memoize((icon: PdkIcon): string => {
  return PDK_DASH_ICON_MAP[icon] ?? '';
});
