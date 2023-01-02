<template>
  <input
    v-model="model"
    type="number"
    :disabled="disabled" />
</template>

<script lang="ts">
import {defineComponent} from 'vue';
import {useVModel} from '@vueuse/core';
/**
 * @see import('@myparcel/pdk-components').DefaultNumberInput
 */
export default defineComponent({
  name: 'WcNumberInput',
  props: {
    /**
     * Controls disabled state.
     */
    disabled: {
      type: Boolean,
    },
    /**
     * The value of the model.
     */
    // eslint-disable-next-line vue/no-unused-properties
    modelValue: {
      type: [String, Number],
      default: null,
    },
  },
  emits: ['update:modelValue'],
  setup: (props, ctx) => ({
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>
