<template>
  <div class="wc-radio-input">
    <input
      :id="`radio_${value}`"
      v-model="model"
      :value="value"
      type="radio"
      :disabled="disabled"
      class=""
    />
    <label
      :for="`radio_${value}`"
      v-text="translate(label)">
    </label>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue';
import {useTranslate} from '@myparcel-pdk/frontend-core';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcRadioInput',

  props: {
    disabled: {
      type: Boolean,
    },

    label: {
      type: String,
      default: '',
    },

    modelValue: {
      type: [String, Number],
      default: null,
    },

    value: {
      type: [String, Number],
      required: true,
    },
  },

  emits: ['update:modelValue'],

  setup: (props, ctx) => ({
    translate: useTranslate(),
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>

