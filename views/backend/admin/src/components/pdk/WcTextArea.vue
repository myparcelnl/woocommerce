<template>
  <textarea
    :id="id"
    v-model.trim="model"
    v-test="AdminComponent.TextArea"
    :name="id"
    :class="{
      'form-required': !element.isValid,
    }"
    :disabled="element.isDisabled || element.isSuspended"
    :readonly="element.isReadOnly" />
</template>

<script lang="ts" setup>
import {useVModel} from '@vueuse/core';
import {AdminComponent, type ElementInstance, generateFieldId} from '@myparcel-dev/pdk-admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: ElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | number) => void>();

const model = useVModel(props, undefined, emit);

const id = generateFieldId(props.element);
</script>
