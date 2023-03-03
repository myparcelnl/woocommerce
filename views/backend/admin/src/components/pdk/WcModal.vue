<template>
  <div
    v-show="isOpen"
    :id="modalKey ? `pdk-modal-${modalKey}` : null"
    v-test="'Modal'"
    :class="[...backgroundClasses, 'mypa-z-[9999]']"
    role="dialog"
    tabindex="-1">
    <Transition :name="config.transitions?.modalBackdrop">
      <div
        v-show="isOpen"
        v-test="'Modal__backdrop'"
        :class="[...backgroundClasses, 'mypa-bg-black', 'mypa-bg-opacity-40']"
        role="none"
        @click="closeModal" />
    </Transition>

    <Transition :name="config.transitions?.modal">
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
        <div class="mypa-relative">
          <span
            :aria-label="translate('action_cancel')"
            class="mypa--m-4 mypa-absolute mypa-cursor-pointer mypa-right-0 mypa-top-0"
            role="button"
            @click="closeModal">
            <PdkIcon icon="close" />
          </span>

          <PdkHeading level="2">
            {{ translate(title) }}
          </PdkHeading>

          <div v-if="isOpen">
            <KeepAlive>
              <NotificationContainer category="modal" />
              <slot :context="context" />
            </KeepAlive>
          </div>

          <PdkButtonGroup>
            <ActionButton
              v-for="(action, index) in actions"
              :key="`action_${getActionIdentifier(action)}_${index}`"
              :action="action"
              :label="action.label" />
          </PdkButtonGroup>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script lang="ts" setup>
import {
  ActionButton,
  AdminModalKey,
  AnyAdminAction,
  NotificationContainer,
  useAdminConfig,
  useLanguage,
  useModalStore,
} from '@myparcel-pdk/admin/src';
import {PropType, computed} from 'vue';
import {getActionIdentifier} from '@myparcel-pdk/frontend-core/src';

const props = defineProps({
  modalKey: {
    type: String as PropType<AdminModalKey>,
    default: null,
  },

  title: {
    type: String,
    required: true,
  },

  actions: {
    type: Array as PropType<AnyAdminAction[]>,
    required: true,
  },
});

const modalStore = useModalStore();

const {translate} = useLanguage();

const isOpen = computed(() => {
  return props.modalKey && modalStore.opened === props.modalKey;
});

const closeModal = () => {
  modalStore.close();
};

const context = computed(() => (isOpen.value ? modalStore.context : null));

const config = useAdminConfig();

const backgroundClasses = ['mypa-left-0', 'mypa-top-0', 'mypa-h-full', 'mypa-w-full', 'mypa-fixed'];
</script>
