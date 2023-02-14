<template>
  <div
    v-test="'RadioInput'"
    class="wc-radio-input">
    <input
      :id="id"
      v-model="model"
      :disabled="element.isDisabled || element.isSuspended"
      :value="element.props?.value"
      type="radio" />
    <label
      :for="id"
      v-text="element.label"></label>
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
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const model = useVModel(props, 'modelValue', emit);
const id = generateFieldId(props.element);
</script>
