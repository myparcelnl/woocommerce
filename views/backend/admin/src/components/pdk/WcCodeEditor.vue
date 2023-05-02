<template>
  <PdkTextArea
    v-model="model"
    :element="element"
    class="mypa-font-mono"
    rows="5" />
</template>

<script lang="ts" setup>
import {ElementInstance} from '@myparcel-pdk/admin/src';
import {useVModel} from '@vueuse/core';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: ElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | number) => void>();

const model = useVModel(props, undefined, emit);
</script>
