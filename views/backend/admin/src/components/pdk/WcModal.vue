<template>
  <div
    v-show="isOpen"
    :id="`pdk-modal-${modalKey}`"
    class="wcmp-bg-black wcmp-bg-opacity-40 wcmp-fixed wcmp-h-full wcmp-left-0 wcmp-overflow-auto wcmp-p-15 wcmp-top-0 wcmp-w-full wcmp-z-50"
    tabindex="-1"
    role="dialog"
    @click="closeModal">
    <div
      class="wcmp-bg-white wcmp-border wcmp-border-gray-400 wcmp-m-15 wcmp-m-auto wcmp-max-w-600 wcmp-p-20 wcmp-w-full"
      role="document"
      @click.stop>
      <div>
        <div>
          <h4 v-text="translate(title)" />
        </div>

        <div v-if="context">
          <NotificationContainer category="modal" />
          <slot :context="context" />
        </div>

        <PdkButtonGroup>
          <ActionButton
            v-for="(action, index) in actions"
            :key="`action_${action.id}_${index}`"
            :action="action.id"
            :label="action.label" />
        </PdkButtonGroup>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {
  ActionButton,
  ModalKey,
  NotificationContainer,
  PdkButtonAction,
  useModalStore,
  useTranslate,
} from '@myparcel/pdk-frontend';
import {PropType, computed, defineComponent, toRefs} from 'vue';

export default defineComponent({
  name: 'WcModal',
  components: {
    NotificationContainer,
    ActionButton,
  },

  props: {
    modalKey: {
      type: String as PropType<ModalKey>,
      default: null,
    },

    title: {
      type: String,
      required: true,
    },

    actions: {
      type: Array as PropType<PdkButtonAction[]>,
      required: true,
    },
  },

  setup: (props) => {
    const propRefs = toRefs(props);
    const modalStore = useModalStore();

    return {
      isOpen: computed(() => {
        return modalStore.opened === propRefs.modalKey.value;
      }),

      translate: useTranslate(),
      context: computed(() => {
        return propRefs.modalKey.value === modalStore.opened ? modalStore.context : null;
      }),

      closeModal() {
        modalStore.close();
      },
    };
  },
});
</script>
