<template>
  <input
    v-model="model"
    :disabled="disabled"
    :type="type" />
</template>

<script lang="ts">
import {useVModel} from '@vueuse/core';
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'WcTextInput',
  props: {
    modelValue: {
      type: [String, Number],
      default: null,
    },

    disabled: {
      type: Boolean,
    },

    type: {
      type: String,
      default: 'text',
    },
  },

  setup: (props, ctx) => ({
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>
