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
      type="radio"
      v-bind="$attrs" />
    <label :for="id">
      <PdkIcon
        v-if="element.props?.icon"
        :icon="element.props.icon" />

      <PdkImage
        v-if="element.props?.image"
        :alt="element.label"
        :src="element.props.image"
        width="24" />

      {{ element.label }}
    </label>
  </div>
</template>

<script lang="ts">
export default {inheritAttrs: false};
</script>

<script lang="ts" setup>
import {useVModel} from '@vueuse/core';
import {type ElementInstance, generateFieldId} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: ElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string) => void>();

const model = useVModel(props, undefined, emit);

const id = generateFieldId(props.element);
</script>
