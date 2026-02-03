<template>
  <PdkTextArea
    v-model="model"
    v-test="AdminComponent.CodeEditor"
    :element="element"
    class="mypa-font-mono"
    rows="5" />
</template>

<script lang="ts" setup>
import {useVModel} from '@vueuse/core';
import {AdminComponent, type ElementInstance} from '@myparcel-dev/pdk-admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: ElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | number) => void>();

const model = useVModel(props, undefined, emit);
</script>
