<template>
  <input
    :id="id"
    v-model="model"
    v-test="'TextInput'"
    :disabled="element.isDisabled || element.isSuspended"
    :type="element.props.type ?? 'text'"
    class="!mypa-w-full" />
</template>

<script lang="ts" setup>
import {ElementInstance, generateFieldId} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';
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
const id = generateFieldId(props.element);
</script>
