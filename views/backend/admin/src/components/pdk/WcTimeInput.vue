<template>
  <input
    :id="id"
    v-model="model"
    :disabled="element.isDisabled || element.isSuspended"
    :type="'time'" />
</template>

<script lang="ts">
import {ElementInstance, generateFieldId} from '@myparcel-pdk/frontend-core/src';
import {PropType, defineComponent} from 'vue';
import {useVModel} from '@vueuse/core';

/**
 * A text input.
 * @see import('@myparcel-pdk/admin-components').DefaultTextInput
 */
export default defineComponent({
  name: 'WcTimeInput',
  props: {
    element: {
      type: Object as PropType<ElementInstance>,
      required: true,
    },

    // eslint-disable-next-line vue/no-unused-properties
    modelValue: {
      type: [String, Number],
      default: null,
    },
  },

  emits: ['update:modelValue'],

  setup: (props, ctx) => {
    return {
      model: useVModel(props, 'modelValue', ctx.emit),
      id: generateFieldId(props.element),
    };
  },
});
</script>
