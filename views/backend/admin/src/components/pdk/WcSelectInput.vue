<template>
  <select
    v-model="model"
    :class="{
      disabled: options.length === 1 || disabled,
      }">
    <option
      v-for="(item, index) in options"
      :key="index"
      :value="item.value"
      v-text="item.label" />
  </select>
</template>

<script lang="ts">
import {useVModel} from '@vueuse/core';
import {PropType, defineComponent} from 'vue';
import {SelectOption} from '@myparcel/pdk-frontend';

export default defineComponent({
  name: 'WcSelectInput',

  props: {
    modelValue: {
      type: [String, Number],
      default: null,
    },

    disabled: {
      type: Boolean,
    },

    options: {
      type: Array as PropType<SelectOption[]>,
      default: (): SelectOption[] => [],
    },
  },

  setup: (props, ctx) => ({
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>
