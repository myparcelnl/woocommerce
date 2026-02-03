<template>
  <div
    v-test="AdminComponent.DropdownButton"
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
        class="mypa-absolute mypa-bg-white mypa-border mypa-border-solid mypa-right-0 mypa-rounded mypa-top-full mypa-z-50">
        <div class="mypa-flex mypa-flex-col">
          <ActionButton
            v-for="(action, index) in dropdownActions.hidden"
            :key="`${index}_${action.id}`"
            :action="action"
            :disabled="disabled"
            :icon="action.icon"
            :variant="action.variant as Variant"
            class="!mypa-px-2 button-link">
            {{ translate(action.label) }}
          </ActionButton>
        </div>
      </div>
    </PdkButton>
  </div>
</template>

<script lang="ts" setup>
import {
  type Variant,
  ActionButton,
  type ActionDefinition,
  type Size,
  useDropdownData,
  useLanguage,
  AdminComponent,
} from '@myparcel-dev/pdk-admin';

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
