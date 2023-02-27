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
import {ElementInstance, generateFieldId} from '@myparcel-pdk/admin/src';
import {InteractiveElementInstance} from '@myparcel/vue-form-builder/src';
import {useVModel} from '@vueuse/core';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: InteractiveElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string) => void>();

const model = useVModel(props, undefined, emit);

const id = generateFieldId(props.element as ElementInstance);
</script>
