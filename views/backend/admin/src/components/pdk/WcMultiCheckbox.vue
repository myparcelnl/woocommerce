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
import {ComputedRef, PropType, computed, ref} from 'vue';
import {ElementInstance} from '@myparcel-pdk/admin/src';
import {SelectOption} from '@myparcel-pdk/common/src';
import {useVModel} from '@vueuse/core';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
  element: {
    type: Object as PropType<ElementInstance>,
    required: true,
  },

  // eslint-disable-next-line vue/no-unused-properties
  modelValue: {
    type: [String, Boolean],
    default: null,
  },
});

const options: ComputedRef<SelectOption[]> = computed(() => props.element.props.options ?? []);

const elements: ComputedRef<ElementInstance[]> = computed(() => {
  return options.value.map((option) => ({
    ...props.element,
    label: option.label,
    ref: ref(props.modelValue === option.value),
  }));
});

const model = useVModel(props, 'modelValue', emit);
</script>
