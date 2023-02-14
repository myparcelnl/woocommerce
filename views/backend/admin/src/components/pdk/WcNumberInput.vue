<template>
  <WcTextInput
    v-model="model"
    v-test="'NumberInput'"
    :element="element"
    type="number" />
</template>

<script lang="ts" setup>
import {ElementInstance} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';
import WcTextInput from './WcTextInput.vue';
import {useVModel} from '@vueuse/core';

const props = defineProps({
  element: {
    type: Object as PropType<ElementInstance>,
    default: null,
  },

  // eslint-disable-next-line vue/no-unused-properties
  modelValue: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const model = useVModel(props, 'modelValue', emit);
</script>
