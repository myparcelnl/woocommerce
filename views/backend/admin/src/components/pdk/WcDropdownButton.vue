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
      size="sm" />

    <PdkButton
      v-if="dropdownActions.hidden.length > 0"
      :aria-expanded="toggled"
      :aria-label="translate('toggle_dropdown')"
      :class="{
        '!mypa-rounded-l-none !mypa-border-l-0': dropdownActions.standalone.length > 0,
      }"
      :disabled="disabled"
      :icon="dropdownIcon"
      aria-haspopup="true"
      class="mypa-relative"
      size="sm"
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
          class="!mypa-border-none">
          {{ translate(action.label) }}
        </ActionButton>
      </div>
    </PdkButton>
  </div>
</template>

<script lang="ts" setup>
import {ActionButton, AdminIcon, ResolvedAction, useDropdownData, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType, computed} from 'vue';

const props = defineProps({
  actions: {
    type: Array as PropType<ResolvedAction[]>,
    default: () => [],
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

const dropdownIcon = computed(() => (toggled.value ? AdminIcon.ARROW_UP : AdminIcon.ARROW_DOWN));

const {translate} = useLanguage();
</script>
