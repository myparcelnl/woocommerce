<template>
  <input
    :id="id"
    v-model="model"
    v-test="'input'"
    :value="value"
    type="checkbox" />
  <label
    v-if="element?.label"
    v-test="'label'"
    :for="id"
    v-text="element?.label" />
</template>

<script lang="ts">
import {ElementInstance, generateFieldId, useLanguage} from '@myparcel/pdk-frontend';
import {PropType, computed, defineComponent} from 'vue';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcCheckboxInput',

  props: {
    element: {
      type: Object as PropType<ElementInstance>,
      default: null,
    },

    // eslint-disable-next-line vue/no-unused-properties
    modelValue: {
      type: [String, Number],
      default: null,
    },
  },

  emits: ['update:modelValue'],

  setup: (props, ctx) => {
    const {translate} = useLanguage();

    return {
      id: generateFieldId(props.element),
      model: useVModel(props, 'modelValue', ctx.emit),
      translate,
      value: computed(() => {
        return props.element?.props?.value ?? '1';
      }),
    };
  },
});
</script>
