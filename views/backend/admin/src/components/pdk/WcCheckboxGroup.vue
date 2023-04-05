<template>
  <div v-test="'CheckboxGroup'">
    <PdkCheckboxInput
      v-for="(option, index) in options"
      :key="`${option.value}_${index}`"
      v-model="model"
      v-test="'CheckboxGroup__option'"
      :element="element" />
  </div>
</template>

<script lang="ts" setup>
import {ElementInstance, OptionsProp, useCheckboxGroupContext} from '@myparcel-pdk/admin/src';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | boolean; element: ElementInstance<OptionsProp<string | boolean>>}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | boolean) => void>();

const {options, model} = useCheckboxGroupContext(props, emit);
</script>
