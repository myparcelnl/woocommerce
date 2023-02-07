<template>
  <div
    v-test="{type: 'wrapper', id}"
    class="wc-radio-input">
    <input
      :id="id"
      v-model="model"
      v-test="{type: 'input', id}"
      :disabled="element.isDisabled || element.isSuspended"
      :value="element.props?.value"
      class=""
      type="radio" />
    <label
      v-test="{type: 'label', id}"
      :for="id"
      v-text="element.label"></label>
  </div>
</template>

<script lang="ts">
import {ElementInstance, generateFieldId} from '@myparcel-pdk/admin/src';
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
