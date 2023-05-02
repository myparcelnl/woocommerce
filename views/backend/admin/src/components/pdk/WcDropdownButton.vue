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
      :disabled="disabled"
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
      <slot />

      <div
        v-show="toggled"
        class="mypa-absolute mypa-bg-white mypa-border mypa-border-solid mypa-flex mypa-flex-col mypa-right-0 mypa-rounded mypa-top-full mypa-z-50">
        <ActionButton
          v-for="(action, index) in dropdownActions.hidden"
          :key="`${index}_${action.id}`"
          v-test="'HiddenDropdownAction'"
          :action="action"
          :disabled="disabled"
          :icon="action.icon"
          :variant="action.variant as Variant"
          class="!mypa-px-2 button-link">
          {{ translate(action.label) }}
        </ActionButton>
      </div>
    </PdkButton>
  </div>
</template>

<script lang="ts" setup>
import {ActionButton, ActionDefinition, Size, useDropdownData, useLanguage} from '@myparcel-pdk/admin/src';
import {Variant} from '@myparcel-pdk/common/src';

const props = defineProps<{
  // eslint-disable-next-line vue/no-unused-properties
  actions: ActionDefinition[];
  disabled: boolean;
  hideText: boolean;
  size: Size;
}>();

const {dropdownActions, toggled, dropdownIcon} = useDropdownData(props);

const {translate} = useLanguage();
</script>
