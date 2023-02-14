import {PdkIcon} from '@myparcel-pdk/admin/src';
import {memoize} from 'lodash-es';

const PDK_DASH_ICON_MAP: Record<PdkIcon, string> = {
  [PdkIcon.ADD]: 'plus',
  [PdkIcon.ARROW_DOWN]: 'arrow-down',
  [PdkIcon.ARROW_UP]: 'arrow-up',
  [PdkIcon.CLOSE]: 'no',
  [PdkIcon.DELETE]: 'trash',
  [PdkIcon.DOWNLOAD]: 'download',
  [PdkIcon.EDIT]: 'edit',
  [PdkIcon.EXPORT]: 'share-alt2',
  [PdkIcon.EXTERNAL]: 'external',
  [PdkIcon.NO]: 'no',
  [PdkIcon.PRINT]: 'printer',
  [PdkIcon.REFRESH]: 'update',
  [PdkIcon.RETURN]: 'undo',
  [PdkIcon.SAVE]: 'yes',
  [PdkIcon.SPINNER]: 'update',
  [PdkIcon.YES]: 'yes',
};

export const pdkIconToDashIcon = memoize((icon: PdkIcon): string => {
  return PDK_DASH_ICON_MAP[icon] ?? '';
});
