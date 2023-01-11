<template>
  <div
    v-show="isOpen"
    :id="`pdk-modal-${modalKey}`"
    :class="[...backgroundClasses, 'wcmp-z-[9999]']"
    tabindex="-1"
    role="dialog">
    <Transition :name="pdkConfig.transitions?.modalBackdrop">
      <div
        v-show="isOpen"
        :class="[...backgroundClasses, 'wcmp-bg-black', 'wcmp-bg-opacity-40']"
        @click="closeModal" />
    </Transition>

    <Transition :name="pdkConfig.transitions?.modal">
      <div
        v-show="isOpen"
        :class="[
          'wcmp-bg-white',
          'wcmp-border',
          'wcmp-border-gray-400',
          'wcmp-fixed',
          'wcmp-left-2',
          'wcmp-m-auto',
          'wcmp-max-w-3xl',
          'wcmp-px-8',
          'wcmp-py-4',
          'wcmp-right-2',
          'wcmp-top-12',
        ]"
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
    </Transition>
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
import {usePdkConfig} from '@myparcel-pdk/frontend-core';

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
      pdkConfig: usePdkConfig(),

      isOpen: computed(() => {
        return modalStore.opened === propRefs.modalKey.value;
      }),

      translate: useTranslate(),
      context: computed(() => {
        return propRefs.modalKey.value === modalStore.opened ? modalStore.context : null;
      }),

      backgroundClasses: ['wcmp-left-0', 'wcmp-top-0', 'wcmp-h-full', 'wcmp-w-full', 'wcmp-fixed'],

      closeModal() {
        modalStore.close();
      },
    };
  },
});
</script>
