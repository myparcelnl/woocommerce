import {AdminAction} from '@myparcel-pdk/frontend-core/src';
import {useActionStore} from '@myparcel-pdk/admin/src';

const BULK_ACTION_PREFIX = 'myparcelnl';

const BULK_ACTION_MAP = Object.freeze({
  action_edit: AdminAction.ORDERS_EDIT,
  action_export: AdminAction.ORDERS_EXPORT,
  action_export_print: AdminAction.ORDERS_EXPORT_PRINT,
  action_print: AdminAction.ORDERS_PRINT,
});

export const onInitialized: () => void = () => {
  jQuery('#doaction').on('click', (event) => {
    const bulkAction = String(jQuery('#bulk-action-selector-top').val());

    if (!bulkAction?.startsWith(BULK_ACTION_PREFIX)) {
      return;
    }

    event.preventDefault();

    const action = bulkAction.replace(`${BULK_ACTION_PREFIX}.`, '') as keyof typeof BULK_ACTION_MAP;

    const adminAction = BULK_ACTION_MAP[action];

    if (!adminAction) {
      throw new Error(`Unknown bulk action: ${action}`);
    }

    const selectedPosts = document.querySelectorAll<HTMLInputElement>('input[name="post[]"]:checked');
    const actionStore = useActionStore();

    actionStore.dispatch(adminAction, {orderIds: [...selectedPosts].map((el) => el.value)});
  });
};
