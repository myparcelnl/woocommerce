<template>
  <a class="wcmp__d--inline-block">
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
      {{ translate(`toggle_${model ? 'yes' : 'no'}`) }}
    </label>
  </a>
</template>

<script lang="ts">
import {ElementInstance, generateFieldId, useTranslate} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';
import {useVModel} from '@vueuse/core';

/**
 * A checkbox. Needs an unique value.
 */
export default defineComponent({
  name: 'DefaultToggleInput',
  props: {
    element: {
      type: Object as PropType<ElementInstance>,
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
      id: generateFieldId(props.element),
      model,
      translate: useTranslate(),
    };
  },
});
</script>
