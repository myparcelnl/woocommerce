<template>
  <input
    v-model="model"
    style="width: 100%"
    :disabled="element.isDisabled || element.isSuspended"
    :type="element.props.type ?? 'text'" />
</template>

<script lang="ts">
import {PropType, UnwrapNestedRefs, defineComponent} from 'vue';
import {InteractiveElementInstance} from '@myparcel-vfb/core';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcTextInput',
  props: {
    element: {
      type: Object as PropType<UnwrapNestedRefs<InteractiveElementInstance>>,
      required: true,
    },

    // eslint-disable-next-line vue/no-unused-properties
    modelValue: {
      type: [String, Number],
      default: null,
    },
  },

  setup: (props, ctx) => ({
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>
