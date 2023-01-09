<template>
  <ActionButton
    v-for="action in standaloneActions"
    :key="action.id"
    :action="action" />

  <PdkButton
    :aria-expanded="toggled"
    :disabled="disabled"
    aria-haspopup="true"
    :aria-label="translate('toggle_dropdown')"
    @focus="toggled = true"
    @focusout="toggled = false"
    @mouseout="toggled = false"
    @mouseover="toggled = true" />

  <div v-show="toggled">
    <ActionButton
      v-for="(action, index) in dropdownActions"
      :key="`${index}_${action.id}`"
      :action="action">
      {{ translate(action.label) }}
    </ActionButton>
  </div>
</template>

<script lang="ts">
import {ActionButton, PdkDropdownAction, useTranslate} from '@myparcel-pdk/frontend-core';
import {PropType, computed, defineComponent, ref, toRefs} from 'vue';

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
    const propRefs = toRefs(props);
    const toggled = ref(false);
    return {
      translate: useTranslate(),
      toggle: () => {
        toggled.value = !toggled.value;
      },

      toggled,
      standaloneActions: computed(() => propRefs.actions.value.filter((option) => option.standalone)),
      dropdownActions: computed(() => propRefs.actions.value.filter((option) => !option.standalone)),
    };
  },
});
</script>
