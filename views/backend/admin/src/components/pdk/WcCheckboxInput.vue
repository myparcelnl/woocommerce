<template>
  <div>
    <input
      :id="id"
      v-model="model"
      v-test="'CheckboxInput'"
      :value="element?.props?.value ?? '1'"
      type="checkbox" />
    <label
      v-if="element?.label"
      v-test="'CheckboxInput__label'"
      :for="id"
      v-text="element?.label" />
  </div>
</template>

<script lang="ts" setup>
import {generateFieldId, useElement} from '@myparcel-pdk/admin/src';
import {useVModel} from '@vueuse/core';

const props = defineProps({
  // eslint-disable-next-line vue/no-unused-properties
  modelValue: {
    type: [String, Boolean, Number, Object, Array],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const model = useVModel(props, undefined, emit);
const id = generateFieldId();
const element = useElement();
</script>
