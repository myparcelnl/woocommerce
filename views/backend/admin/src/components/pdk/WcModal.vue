<template>
  <div
    v-show="isOpen"
    :id="`pdk-modal-${modalKey}`"
    class="wcmp-modal"
    tabindex="-1"
    role="dialog"
    @click="closeModal">
    <div
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

        <div>
          <div>
            <PdkButton
              v-for="(action, index) in actions"
              :key="`action_${action.id}_${index}`"
              :action="action.id"
              :label="action.label" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {ModalKey, NotificationContainer, PdkButtonAction, useModalStore, useTranslate} from '@myparcel/pdk-frontend';
import {PropType, computed, defineComponent, toRefs} from 'vue';

export default defineComponent({
  name: 'WcModal',
  components: {
    NotificationContainer,
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
