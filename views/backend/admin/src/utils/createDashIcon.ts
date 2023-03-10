import {AdminIcon} from '@myparcel-pdk/admin/src';
import {memoize} from 'lodash-unified';

const PDK_DASH_ICON_MAP: Partial<Record<AdminIcon, string>> = {
  [AdminIcon.Add]: 'plus',
  [AdminIcon.ArrowDown]: 'arrow-down',
  [AdminIcon.ArrowUp]: 'arrow-up',
  [AdminIcon.Close]: 'no',
  [AdminIcon.Delete]: 'trash',
  [AdminIcon.Download]: 'download',
  [AdminIcon.Edit]: 'edit',
  [AdminIcon.Export]: 'share-alt2',
  [AdminIcon.External]: 'external',
  [AdminIcon.No]: 'no',
  [AdminIcon.Print]: 'printer',
  [AdminIcon.Refresh]: 'update',
  [AdminIcon.Return]: 'undo',
  [AdminIcon.Save]: 'yes',
  [AdminIcon.Spinner]: 'update',
  [AdminIcon.Yes]: 'yes',
};

export const createDashIcon = memoize((icon: AdminIcon): string | undefined => {
  return PDK_DASH_ICON_MAP[icon];
});
