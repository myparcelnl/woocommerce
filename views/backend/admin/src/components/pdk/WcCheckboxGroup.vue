<template>
  <div v-test="AdminComponent.CheckboxGroup">
    <PdkCheckboxInput
      v-for="(option, index) in options"
      :key="`${option.value}_${index}`"
      v-model="model"
      :element="element" />
  </div>
</template>

<script lang="ts" setup>
import {type ElementInstance, type OptionsProp, useCheckboxGroupContext, AdminComponent} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | boolean; element: ElementInstance<OptionsProp<string | boolean>>}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | boolean) => void>();

const {options, model} = useCheckboxGroupContext(props, emit);
</script>
