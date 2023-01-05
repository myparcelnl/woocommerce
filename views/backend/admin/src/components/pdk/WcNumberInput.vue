<template>
  <input
    v-model="model"
    type="number"
    :disabled="element.isDisabled" />
</template>

<script lang="ts">
import {PropType, UnwrapNestedRefs, defineComponent} from 'vue';
import {InteractiveElementInstance} from '@myparcel-vfb/core';
import {useVModel} from '@vueuse/core';

/**
 * @see import('@myparcel/pdk-components').DefaultNumberInput
 */
export default defineComponent({
  name: 'WcNumberInput',
  props: {
    element: {
      type: Object as PropType<UnwrapNestedRefs<InteractiveElementInstance>>,
      required: true,
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
