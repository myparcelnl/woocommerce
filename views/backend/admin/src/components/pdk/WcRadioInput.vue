<template>
  <div
    v-test="'RadioInput'"
    class="wc-radio-input">
    <input
      :id="id"
      v-model="model"
      :class="{
        'form-required': !element.isValid,
      }"
      :disabled="element.isDisabled || element.isSuspended"
      :value="element.props?.value"
      type="radio" />
    <label
      :for="id"
      v-text="element.label"></label>
  </div>
</template>

<script lang="ts" setup>
import {generateFieldId, useElement} from '@myparcel-pdk/admin/src';
import {useVModel} from '@vueuse/core';

const props = defineProps({
  // eslint-disable-next-line vue/no-unused-properties
  modelValue: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const model = useVModel(props, undefined, emit);
const id = generateFieldId();
const element = useElement();
</script>
