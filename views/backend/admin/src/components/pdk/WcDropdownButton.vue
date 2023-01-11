<template>
  <div>
    <ActionButton
      v-for="action in standaloneActions"
      :key="action.id"
      :action="action" />

    <PdkButton
      :aria-expanded="toggled"
      :disabled="disabled"
      aria-haspopup="true"
      :aria-label="translate('toggle_dropdown')"
      class="mypa-relative"
      @focus="toggled = true"
      @focusout="toggled = false"
      @click="toggled = !toggled"
      @mouseout="toggled = false"
      @mouseover="toggled = true">
      <PdkIcon :icon="icon" />

      <div
        v-show="toggled"
        class="mypa-absolute mypa-bg-white mypa-border mypa-border-solid mypa-flex mypa-flex-col mypa-right-0 mypa-top-full mypa-z-50">
        <ActionButton
          v-for="(action, index) in dropdownActions"
          :key="`${index}_${action.id}`"
          class="!mypa-border-none"
          :action="action">
          {{ translate(action.label) }}
        </ActionButton>
      </div>
    </PdkButton>
  </div>
</template>

<script lang="ts">
import {ActionButton, PdkDropdownAction, useTranslate} from '@myparcel-pdk/frontend-core';
import {PropType, computed, defineComponent, ref} from 'vue';
import {PdkIcon} from '@myparcel/pdk-frontend';

export default defineComponent({
  name: 'WcDropdownButton',
  components: {
    ActionButton: ActionButton,
  },

  props: {
    disabled: {
      type: Boolean,
    },

    actions: {
      type: Array as PropType<PdkDropdownAction[]>,
      default: (): never[] => [],
    },
  },

  emits: ['click'],
  setup: (props) => {
    const toggled = ref(false);

    return {
      icon: PdkIcon.ARROW_DOWN,

      translate: useTranslate(),
      toggle: () => {
        toggled.value = !toggled.value;
      },

      toggled,
      standaloneActions: computed(() => props.actions.filter((option) => option.standalone)),
      dropdownActions: computed(() => props.actions.filter((option) => !option.standalone)),
    };
  },
});
</script>
