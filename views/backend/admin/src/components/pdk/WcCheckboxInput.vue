<template>
    <input
      :id="`checkbox_${value}`"
      v-model="model"
      :value="value"
      type="checkbox"
      class="myparcel-checkbox">
    <label
      v-text="translate(label)"
      :for="`checkbox_${value}`" />
</template>

<script lang="ts">
import {defineComponent} from 'vue';
import {useTranslate} from '@myparcel/pdk-frontend';
import {useVModel} from '@vueuse/core';

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
    translate: useTranslate(),
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>
