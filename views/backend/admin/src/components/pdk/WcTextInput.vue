<template>
  <input
    :id="id"
    v-model.trim="model"
    v-test="'TextInput'"
    :class="{
      'form-required': !element.isValid,
    }"
    :disabled="element.isDisabled || element.isSuspended"
    :type="element.props?.type ?? 'text'"
    class="!mypa-w-full" />
</template>

<script lang="ts" setup>
import {useVModel} from '@vueuse/core';
import {type ElementInstance, generateFieldId} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: ElementInstance<{type?: string}>}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | number) => void>();

const model = useVModel(props, undefined, emit);

const id = generateFieldId(props.element);
</script>
