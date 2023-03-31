<template>
  <PdkTextArea
    v-model="model"
    :element="element"
    class="mypa-font-mono"
    rows="5" />
</template>

<script lang="ts" setup>
import {InteractiveElementInstance} from '@myparcel/vue-form-builder/src';
import {useVModel} from '@vueuse/core';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: InteractiveElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | number) => void>();

const model = useVModel(props, undefined, emit);
</script>
