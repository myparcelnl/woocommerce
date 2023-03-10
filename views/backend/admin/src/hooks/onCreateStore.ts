import {useModalStore} from '@myparcel-pdk/admin/src';

export const onCreateStore = (): void => {
  const modalStore = useModalStore();

  const closeOnEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
      modalStore.close();
    }
  };

  modalStore.onOpen(() => {
    document.addEventListener('keydown', closeOnEscape);
  });

  modalStore.onClose(() => {
    document.removeEventListener('keydown', closeOnEscape);
  });
};
