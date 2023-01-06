<template>
  <WcTextInput
    v-model="model"
    :element="element"
    type="number" />
</template>

<script lang="ts">
import {PropType, UnwrapNestedRefs, defineComponent} from 'vue';
import {InteractiveElementInstance} from '@myparcel-vfb/core';
import WcTextInput from './WcTextInput.vue';
import {useVModel} from '@vueuse/core';

/**
 * @see import('@myparcel/pdk-components').DefaultNumberInput
 */
export default defineComponent({
  name: 'WcNumberInput',
  components: {WcTextInput},
  props: {
    element: {
      type: Object as PropType<UnwrapNestedRefs<InteractiveElementInstance>>,
      required: true,
    },

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
