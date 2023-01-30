<template>
  <div>
    <PdkCheckboxInput
      v-for="(option, index) in options"
      :key="`${option.value}_${index}`"
      v-model="model"
      v-test
      :element="elements[index]" />
  </div>
</template>

<script lang="ts">
import {ComputedRef, PropType, computed, defineComponent, ref} from 'vue';
import {ElementInstance, useLanguage} from '@myparcel/pdk-frontend';
import {SelectOption} from '@myparcel-pdk/common';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcMultiCheckbox',
  props: {
    element: {
      type: Object as PropType<ElementInstance>,
      required: true,
    },

    // eslint-disable-next-line vue/no-unused-properties
    modelValue: {
      type: [String, Boolean],
      default: null,
    },
  },

  emits: ['update:modelValue'],

  setup: (props, ctx) => {
    const {translate} = useLanguage();

    const options: ComputedRef<SelectOption[]> = computed(() => props.element.props.options ?? []);

    const elements: ComputedRef<ElementInstance[]> = computed(() => {
      return options.value.map((option) => ({
        ...props.element,
        label: option.label,
        ref: ref(props.modelValue === option.value),
      }));
    });

    return {
      options,
      model: useVModel(props, 'modelValue', ctx.emit),
      elements,
      translate,
    };
  },
});
</script>
