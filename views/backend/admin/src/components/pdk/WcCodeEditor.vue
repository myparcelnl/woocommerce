<template>
  <WcTextArea
    v-model="model"
    style="font-family: monospace; font-size: 12px"
    element=""></WcTextArea>
</template>

<script lang="ts" setup>
import WcTextArea from './WcTextArea.vue';
import {InteractiveElementInstance} from '@myparcel/vue-form-builder/src';
import {useVModel} from '@vueuse/core';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: InteractiveElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | number) => void>();

const model = useVModel(props, undefined, emit);
</script>
