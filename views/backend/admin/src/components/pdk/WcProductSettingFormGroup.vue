<template>
  <p class="form-field">
    <label :for="id">{{ translate(element.label) }}</label>

    <span
      v-if="element.props.description"
      class="woocommerce-help-tip"
      :data-tip="translate(element.props.description)" />

    <slot />
  </p>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue';
import {ElementInstance, useLanguage, generateFieldId, usePdkConfig} from '@myparcel/pdk-frontend';

export default defineComponent({
  name: 'WcProductSettingFormGroup',
  props: {
    element: {
      type: Object as PropType<ElementInstance>,
      required: true,
    },
  },

  setup: (props) => {
    const { translate } = useLanguage();

    return {
      id: generateFieldId(props.element),
      config: usePdkConfig(),
      translate,
    };
  },
});
</script>
