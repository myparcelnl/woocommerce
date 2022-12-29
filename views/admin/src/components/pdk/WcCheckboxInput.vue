<template>
    <input
      :id="`checkbox_${value}`"
      v-model="model"
      :value="value"
      type="checkbox"
      class="myparcel-checkbox">
    <label
      v-t="label"
      :for="`checkbox_${value}`" />
</template>

<script lang="ts">
import {useVModel} from '@vueuse/core';
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'WcCheckboxInput',

  props: {
    modelValue: {
      type: [String, Number],
      default: null,
    },

    value: {
      type: [String, Number],
      required: true,
    },

    disabled: {
      type: Boolean,
      default: false,
    },

    label: {
      type: String,
      default: '',
    },
  },

  setup: (props, ctx) => ({
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>
