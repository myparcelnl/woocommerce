<template>
  <div class="wc-radio-input">
    <input
      :id="`radio_${element.props?.value}`"
      v-model="model"
      :value="element.props?.value"
      type="radio"
      :disabled="element.isDisabled || element.isSuspended"
      class="" />
    <label
      :for="`radio_${element.props?.value}`"
      v-text="element.label"></label>
  </div>
</template>

<script lang="ts">
import {PropType, UnwrapNestedRefs, defineComponent} from 'vue';
import {InteractiveElementInstance} from '@myparcel-vfb/core';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcRadioInput',

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
