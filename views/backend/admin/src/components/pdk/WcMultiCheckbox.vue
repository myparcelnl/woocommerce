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
import {ElementInstance, useElement} from '@myparcel-pdk/admin/src';
import {SelectOption} from '@myparcel-pdk/common/src';
import {useVModel} from '@vueuse/core';

const props = defineProps({
  // eslint-disable-next-line vue/no-unused-properties
  modelValue: {
    type: [String, Boolean, Array, Object],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const element = useElement();

const options: ComputedRef<SelectOption[]> = computed(() => element.props?.options ?? []);

const elements: ComputedRef<ElementInstance[]> = computed(() => {
  return options.value.map((option) => ({
    ...element,
    label: option.label,
    ref: ref(props.modelValue === option.value),
  }));
});

const model = useVModel(props, undefined, emit);
</script>
