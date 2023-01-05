<template>
  <a class="wcmp__d--inline-block wcmp__toggle">
    <input
      :id="id"
      v-model="model"
      :disabled="element.isDisabled || element.isSuspended"
      :value="true"
      type="checkbox"
      style="display: none" />

    <label
      :for="id"
      :class="`woocommerce-input-toggle woocommerce-input-toggle--${model ? 'enabled' : 'disabled'}`">
      {{ model ? element.props.labelYes : element.props.labelNo }}
    </label>
  </a>
</template>

<script lang="ts">
import {PropType, UnwrapNestedRefs, defineComponent} from 'vue';
import {InteractiveElementInstance} from '@myparcel-vfb/core';
import {generateId} from '@myparcel/pdk-frontend';
import {useVModel} from '@vueuse/core';

/**
 * A checkbox. Needs an unique value.
 */
export default defineComponent({
  name: 'DefaultToggleInput',
  props: {
    element: {
      type: Object as PropType<UnwrapNestedRefs<InteractiveElementInstance>>,
      required: true,
    },

    // eslint-disable-next-line vue/no-unused-properties
    modelValue: {
      type: [String, Boolean],
      default: false,
    },
  },

  emits: ['update:modelValue'],

  setup: (props, ctx) => {
    const model = useVModel(props, 'modelValue', ctx.emit);

    return {
      id: generateId(),
      model,
    };
  },
});
</script>
