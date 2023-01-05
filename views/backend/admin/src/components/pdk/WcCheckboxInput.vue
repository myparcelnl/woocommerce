<template>
  <input
    :id="`checkbox_${element.props.value}`"
    v-model="model"
    :value="element.props.value"
    type="checkbox"
    class="myparcel-checkbox" />
  <label
    :for="`checkbox_${element.props.value}`"
    v-text="element.label" />
</template>

<script lang="ts">
import {PropType, UnwrapNestedRefs, defineComponent} from 'vue';
import {InteractiveElementInstance} from '@myparcel-vfb/core';
import {useTranslate} from '@myparcel/pdk-frontend';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcCheckboxInput',

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
    translate: useTranslate(),
    model: useVModel(props, 'modelValue', ctx.emit),
  }),
});
</script>
