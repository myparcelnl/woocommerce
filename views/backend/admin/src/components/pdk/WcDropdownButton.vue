<template>
  <div class="mypa-flex">
    <ActionButton
      v-for="(action, index) in dropdownActions.standalone"
      :key="action.id"
      size="sm"
      :class="{
        '!mypa-rounded-r-none': index === dropdownActions.standalone.length - 1,
      }"
      :hide-text="hideText"
      :action="action" />

    <PdkButton
      v-if="dropdownActions.hidden.length > 0"
      :aria-expanded="toggled"
      :disabled="disabled"
      aria-haspopup="true"
      :aria-label="translate('toggle_dropdown')"
      class="mypa-relative"
      :class="{
        '!mypa-rounded-l-none': dropdownActions.hidden.length > 1,
      }"
      size="sm"
      :icon="dropdownIcon"
      @focus="toggled = true"
      @focusout="toggled = false"
      @mouseout="toggled = false"
      @mouseover="toggled = true">
      <div
        v-show="toggled"
        class="mypa-absolute mypa-bg-white mypa-border mypa-border-solid mypa-flex mypa-flex-col mypa-right-0 mypa-top-full mypa-z-50">
        <ActionButton
          v-for="(action, index) in dropdownActions.hidden"
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
import {ActionButton, PdkDropdownAction, PdkIcon, useDropdownData, useLanguage} from '@myparcel/pdk-frontend';
import {PropType, computed, defineComponent} from 'vue';

export default defineComponent({
  name: 'WcDropdownButton',
  components: {
    ActionButton: ActionButton,
  },

  props: {
    actions: {
      type: Array as PropType<PdkDropdownAction[]>,
      default: () => [],
    },

    disabled: {
      type: Boolean,
    },

    hideText: {
      type: Boolean,
    },
  },

  emits: ['click'],
  setup: (props) => {
    const {translate} = useLanguage();
    const dropdownData = useDropdownData(props.actions);

    return {
      ...dropdownData,

      dropdownIcon: computed(() => {
        return dropdownData.toggled.value ? PdkIcon.ARROW_UP : PdkIcon.ARROW_DOWN;
      }),

      translate,
    };
  },
});
</script>
