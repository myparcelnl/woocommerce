<template>
  <div>
    <input
      :id="id"
      v-model="model"
      v-test="'CheckboxInput'"
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
import {PropType} from 'vue';
import {useVModel} from '@vueuse/core';

const props = defineProps({
  element: {
    type: Object as PropType<ElementInstance>,
    default: null,
  },

  // eslint-disable-next-line vue/no-unused-properties
  modelValue: {
    type: [String, Number, Object, Array, Boolean],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const model = useVModel(props, 'modelValue', emit);
const id = generateFieldId(props.element);
</script>
