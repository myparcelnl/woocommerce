<template>
  <input
    :id="id"
    v-model="model"
    v-test="{type: 'input', id}"
    :disabled="element.isDisabled || element.isSuspended"
    :type="element.props.type ?? 'text'"
    class="!mypa-w-full" />
</template>

<script lang="ts">
import {ElementInstance, generateFieldId} from '@myparcel-pdk/admin';
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
