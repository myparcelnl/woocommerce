<template>
  <div
    v-show="isOpen"
    id="poststuff"
    v-test="AdminComponent.Modal"
    :class="[...backgroundClasses, 'mypa-z-[9999]']"
    role="dialog"
    tabindex="-1"
    @keydown.esc="close">
    <Transition :name="config.transitions?.modalBackdrop">
      <div
        v-show="isOpen"
        :class="[...backgroundClasses, 'mypa-bg-black', 'mypa-bg-opacity-40']"
        role="none"
        @click="close" />
    </Transition>

    <div
      v-show="isOpen"
      :class="['mypa-fixed', 'mypa-left-2', 'mypa-m-auto', 'mypa-max-w-2xl', 'mypa-right-2', 'mypa-top-12']"
      role="document"
      @click.stop>
      <PdkBox :actions="actions">
        <template #header>
          <div class="mypa-relative mypa-w-full">
            <span
              :aria-label="translate('action_close')"
              class="mypa-absolute mypa-cursor-pointer mypa-right-0"
              role="button"
              @click="close">
              <PdkIcon icon="close" />
            </span>

            <span v-text="translate(title)" />
          </div>
        </template>

        <template #default>
          <div
            v-if="isOpen"
            class="inside">
            <NotificationContainer :category="NotificationCategory.Modal" />

            <KeepAlive>
              <slot :context="context" />
            </KeepAlive>
          </div>
        </template>
      </PdkBox>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {type PropType, toRefs} from 'vue';
import {
  AdminComponent,
  type ActionDefinition,
  type AdminModalKey,
  NotificationContainer,
  useAdminConfig,
  useLanguage,
  useModalElementContext,
  NotificationCategory,
} from '@myparcel-pdk/admin';

const props = defineProps({
  actions: {
    type: Array as PropType<ActionDefinition[]>,
    default: () => [],
  },

  modalKey: {
    type: String as PropType<AdminModalKey>,
    default: null,
  },

  title: {
    type: String,
    required: true,
  },
});

const propRefs = toRefs(props);

const config = useAdminConfig();

const {isOpen, context, close} = useModalElementContext(propRefs.modalKey?.value);

const {translate} = useLanguage();

const backgroundClasses = ['mypa-left-0', 'mypa-top-0', 'mypa-h-full', 'mypa-w-full', 'mypa-fixed'];
</script>
