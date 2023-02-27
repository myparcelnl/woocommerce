<template>
  <div v-test="'MultiCheckbox'">
    <PdkCheckboxInput
      v-for="(option, index) in options"
      :key="`${option.value}_${index}`"
      v-model="model"
      v-test="'MultiCheckbox__option'"
      :element="elements[index]" />
  </div>
</template>

<script lang="ts" setup>
import {ComputedRef, computed, ref} from 'vue';
import {InteractiveElementInstance} from '@myparcel/vue-form-builder/src';
import {SelectOption} from '@myparcel-pdk/common/src';
import {useVModel} from '@vueuse/core';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{
  modelValue: string | boolean | unknown[] | Record<string, unknown>;
  element: InteractiveElementInstance;
}>();
const emit =
  defineEmits<(e: 'update:modelValue', value: string | boolean | unknown[] | Record<string, unknown>) => void>();

const model = useVModel(props, undefined, emit);

// @ts-expect-error props are not typed
const options: ComputedRef<SelectOption[]> = computed(() => props.element.props?.options ?? []);

const elements: ComputedRef<InteractiveElementInstance[]> = computed(() => {
  return options.value.map((option) => ({
    ...props.element,
    label: option.label,
    ref: ref(props.modelValue === option.value),
  }));
});
</script>
