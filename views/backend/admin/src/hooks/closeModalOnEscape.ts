import {useModalStore} from '@myparcel-pdk/admin';

export const closeModalOnEscape = (): void => {
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
