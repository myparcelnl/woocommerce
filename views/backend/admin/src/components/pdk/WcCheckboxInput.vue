<template>
  <input
    :id="`checkbox_${value}`"
    v-model="model"
    :value="value"
    type="checkbox"
    class="myparcel-checkbox" />
  <label
    v-if="element?.label"
    :for="`checkbox_${value}`"
    v-text="element?.label" />
</template>

<script lang="ts">
import {PropType, UnwrapNestedRefs, computed, defineComponent} from 'vue';
import {InteractiveElementInstance} from '@myparcel-vfb/core';
import {useTranslate} from '@myparcel/pdk-frontend';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcCheckboxInput',

  props: {
    element: {
      type: Object as PropType<UnwrapNestedRefs<InteractiveElementInstance>>,
      default: null,
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
    value: computed(() => {
      // @ts-expect-error props are not typed correctly
      return props.element?.props?.value ?? '1';
    }),
  }),
});
</script>
