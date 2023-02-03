<template>
  <a v-test="{type: 'wrapper', id}">
    <input
      :id="id"
      v-model="model"
      v-test="{type: 'input', id}"
      :disabled="element.isDisabled || element.isSuspended"
      :value="true"
      class="!mypa-hidden"
      type="checkbox" />

    <label
      v-test="{type: 'label', id}"
      :class="`woocommerce-input-toggle woocommerce-input-toggle--${model ? 'enabled' : 'disabled'}`"
      :for="id"
      class="!mypa-float-none !mypa-ml-auto !mypa-w-8">
      {{ translate(`toggle_${model ? 'yes' : 'no'}`) }}
    </label>
  </a>
</template>

<script lang="ts">
import {ElementInstance, generateFieldId, useLanguage} from '@myparcel-pdk/admin';
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
    const { translate } = useLanguage();

    return {
      id: generateFieldId(props.element),
      model: useVModel(props, 'modelValue', ctx.emit),
      translate: translate,
    };
  },
});
</script>
