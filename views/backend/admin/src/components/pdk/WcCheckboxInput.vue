<template>
  <div>
    <input
      :id="id"
      v-model="model"
      v-test="'CheckboxInput'"
      :class="{
        'form-required': !element.isValid,
      }"
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
import {ElementInstance, generateFieldId} from '@myparcel-pdk/admin/src';
import {InteractiveElementInstance} from '@myparcel/vue-form-builder/src';
import {useVModel} from '@vueuse/core';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | boolean | number | unknown[]; element: InteractiveElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | boolean | number | unknown[]) => void>();

const model = useVModel(props, undefined, emit);

const id = generateFieldId(props.element as ElementInstance);
</script>
