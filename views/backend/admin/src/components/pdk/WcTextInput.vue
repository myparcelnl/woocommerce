<template>
  <input
    :id="id"
    v-model.trim="model"
    v-test="AdminComponent.TextInput"
    :name="id"
    :class="{
      'form-required': !element.isValid,
    }"
    :disabled="element.isDisabled || element.isSuspended"
    :readonly="element.isReadOnly"
    :type="element.props?.type ?? 'text'" />
</template>

<script lang="ts" setup>
import {useVModel} from '@vueuse/core';
import {type ElementInstance, generateFieldId, AdminComponent} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: ElementInstance<{type?: string}>}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | number) => void>();

const model = useVModel(props, undefined, emit);

const id = generateFieldId(props.element);
</script>
