<template>
  <p
    v-test="id"
    class="form-field">
    <label :for="id">{{ translate(element.label) }}</label>

    <span
      v-if="element.props.description"
      :data-tip="translate(element.props.description)"
      class="woocommerce-help-tip" />

    <slot />
  </p>
</template>

<script lang="ts">
import {ElementInstance, generateFieldId, useLanguage, usePdkConfig} from '@myparcel-pdk/admin';
import {PropType, defineComponent} from 'vue';

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
