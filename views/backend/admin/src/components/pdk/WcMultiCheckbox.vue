<template>
  <div v-test="'MultiCheckbox'">
    <PdkCheckboxInput
      v-for="(option, index) in options"
      :key="`${option.value}_${index}`"
      v-model="model"
      v-test="'MultiCheckbox__option'"
      :element="element" />
  </div>
</template>

<script lang="ts" setup>
import {ElementInstance, OptionsProp, useMultiCheckboxInputContext} from '@myparcel-pdk/admin/src';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | boolean; element: ElementInstance<OptionsProp<string | boolean>>}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | boolean) => void>();

// @ts-expect-error todo
const {options, model} = useMultiCheckboxInputContext(props, emit);
</script>
