<template>
  <input
    :id="id"
    v-model="model"
    class="mypa-w-full mypa-mw-48"
    :disabled="element.isDisabled || element.isSuspended"
    :type="element.props.type ?? 'text'" />
</template>

<script lang="ts">
import {ElementInstance, generateFieldId} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcTextInput',
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

  setup: (props, ctx) => ({
    id: generateFieldId(props.element),
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>
