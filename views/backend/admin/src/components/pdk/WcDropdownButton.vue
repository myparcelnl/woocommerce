<template>
  <div class="mypa-flex">
    <ActionButton
      v-for="(action, index) in standaloneActions"
      :key="action.id"
      size="sm"
      :class="{
        '!mypa-rounded-r-none': index === standaloneActions.length - 1,
      }"
      :hide-text="hideText"
      :action="action" />

    <PdkButton
      v-if="dropdownActions.length > 0"
      :aria-expanded="clickToggled || hoverToggled"
      :disabled="disabled"
      aria-haspopup="true"
      :aria-label="translate('toggle_dropdown')"
      class="mypa-relative"
      :class="{
        '!mypa-rounded-l-none': standaloneActions.length > 1,
      }"
      size="sm"
      :icon="dropdownIcon"
      @focus="hoverToggled = true"
      @focusout="hoverToggled = false"
      @keydown.enter="clickToggled = !clickToggled"
      @click="clickToggled = !clickToggled"
      @mouseout="hoverToggled = false"
      @mouseover="hoverToggled = true">
      <div
        v-show="clickToggled || hoverToggled"
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
import {ActionButton, PdkDropdownAction, PdkIcon, useLanguage} from '@myparcel/pdk-frontend';
import {PropType, computed, defineComponent, ref} from 'vue';

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
    const hoverToggled = ref(false);
    const clickToggled = ref(false);
    const {translate} = useLanguage();

    return {
      clickToggled,
      hoverToggled,

      dropdownIcon: computed(() => {
        return hoverToggled.value ? PdkIcon.ARROW_UP : PdkIcon.ARROW_DOWN;
      }),

      dropdownActions: computed(() => props.actions.filter((option) => !option.standalone)),
      standaloneActions: computed(() => props.actions.filter((option) => option.standalone)),

      translate,
    };
  },
});
</script>
