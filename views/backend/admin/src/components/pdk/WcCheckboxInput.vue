<template>
  <div>
    <input
      :id="id"
      v-model="model"
      v-test="[AdminComponent.CheckboxInput, element]"
      :class="{
        'form-required': !element.isValid,
      }"
      :disabled="element.isDisabled || element.isSuspended || element.isReadOnly"
      :name="id"
      :readonly="element.isReadOnly"
      :value="element?.props?.value ?? '1'"
      type="checkbox"
      v-bind="$attrs" />
    <label
      v-if="element?.label"
      :for="id"
      v-text="element?.label" />
  </div>
</template>

<script lang="ts">
export default {inheritAttrs: false};
</script>

<script lang="ts" setup>
import {useVModel} from '@vueuse/core';
import {AdminComponent, type ElementInstance, generateFieldId} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | boolean | number | unknown[]; element: ElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | boolean | number | unknown[]) => void>();

const model = useVModel(props, undefined, emit);

const id = generateFieldId(props.element);
</script>
