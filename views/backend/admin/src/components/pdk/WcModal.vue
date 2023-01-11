<template>
  <div
    v-show="isOpen"
    :id="`pdk-modal-${modalKey}`"
    :class="[...backgroundClasses, 'mypa-z-[9999]']"
    tabindex="-1"
    role="dialog">
    <Transition :name="pdkConfig.transitions?.modalBackdrop">
      <div
        v-show="isOpen"
        :class="[...backgroundClasses, 'mypa-bg-black', 'mypa-bg-opacity-40']"
        @click="closeModal" />
    </Transition>

    <Transition :name="pdkConfig.transitions?.modal">
      <div
        v-show="isOpen"
        :class="[
          'mypa-bg-white',
          'mypa-border',
          'mypa-border-gray-400',
          'mypa-fixed',
          'mypa-left-2',
          'mypa-m-auto',
          'mypa-max-w-3xl',
          'mypa-px-8',
          'mypa-py-4',
          'mypa-right-2',
          'mypa-top-12',

        ]"
        role="document"
        @click.stop>
        <div>
          <PdkHeading level="2">
            {{ translate(title) }}
          </PdkHeading>

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

      backgroundClasses: ['mypa-left-0', 'mypa-top-0', 'mypa-h-full', 'mypa-w-full', 'mypa-fixed'],

      closeModal() {
        modalStore.close();
      },
    };
  },
});
</script>
