<template>
  <div class="multicheckbox">
    <PdkCheckboxInput
      v-for="(option, index) in options"
      :key="`${option.value}_${index}`"
      :disabled="option.disabled || disabled"
      :label="option.label"
      :value="option.value" />
  </div>
</template>

<script lang="ts">
import {PropType, defineComponent} from 'vue';
import {SelectOption} from '@myparcel-pdk/common';

export default defineComponent({
  name: 'WcMultiCheckbox',
  props: {
    disabled: {
      type: Boolean,
    },

    options: {
      type: Array as PropType<SelectOption[]>,
      default: () => [],
    },

    // eslint-disable-next-line vue/no-unused-properties
    modelValue: {
      type: String,
      default: null,
    },
  },
});
</script>
