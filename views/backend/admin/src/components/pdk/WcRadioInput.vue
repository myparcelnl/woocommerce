<template>
  <div
    v-test="'wrapper'"
    class="wc-radio-input">
    <input
      :id="id"
      v-model="model"
      v-test="'input'"
      :value="element.props?.value"
      type="radio"
      :disabled="element.isDisabled || element.isSuspended"
      class="" />
    <label
      v-test="'label'"
      :for="id"
      v-text="element.label"></label>
  </div>
</template>

<script lang="ts">
import {ElementInstance, generateFieldId} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcRadioInput',

  props: {
    element: {
      type: Object as PropType<ElementInstance>,
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
    id: generateFieldId(props.element),
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>
