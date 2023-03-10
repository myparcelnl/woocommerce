<template>
  <div
    v-test="'DropdownButton'"
    class="mypa-flex">
    <ActionButton
      v-for="(action, index) in dropdownActions.standalone"
      :key="action.id"
      :action="action"
      :class="{
        '!mypa-rounded-r-none': index === dropdownActions.standalone.length - 1,
      }"
      :hide-text="hideText"
      :size="size" />

    <PdkButton
      v-if="dropdownActions.hidden.length > 0"
      :aria-expanded="toggled"
      :aria-label="translate('toggle_dropdown')"
      :class="{
        '!mypa-rounded-l-none !mypa-border-l-0': dropdownActions.standalone.length > 0,
      }"
      :disabled="disabled"
      :icon="dropdownIcon"
      :size="size"
      aria-haspopup="true"
      class="mypa-relative"
      @focus="toggled = true"
      @focusout="toggled = false"
      @mouseout="toggled = false"
      @mouseover="toggled = true">
      <div
        v-show="toggled"
        class="mypa-absolute mypa-bg-white mypa-border mypa-border-solid mypa-flex mypa-flex-col mypa-right-0 mypa-rounded mypa-top-full mypa-z-50">
        <ActionButton
          v-for="(action, index) in dropdownActions.hidden"
          :key="`${index}_${action.id}`"
          v-test="'HiddenDropdownAction'"
          :action="action"
          class="!mypa-border-none !mypa-text-left">
          {{ translate(action.label) }}
        </ActionButton>
      </div>
    </PdkButton>
  </div>
</template>

<script lang="ts" setup>
import {ActionButton, ActionDefinition, AdminIcon, Size, useDropdownData, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType, computed} from 'vue';

const props = defineProps({
  actions: {
    type: Array as PropType<ActionDefinition[]>,
    default: () => [],
  },

  size: {
    type: String as PropType<Size>,
    default: 'sm',
  },

  disabled: {
    type: Boolean,
  },

  hideText: {
    type: Boolean,
  },
});

defineEmits(['click']);

const {dropdownActions, toggled} = useDropdownData(props.actions);

const dropdownIcon = computed(() => (toggled.value ? AdminIcon.ArrowUp : AdminIcon.ArrowDown));

const {translate} = useLanguage();
</script>
