<template>
  <PdkTableRow valign="top">
    <PdkTableCol
      component="th"
      scope="row"
      class="titledesc">
      <label
        :class="config?.cssUtilities?.whitespaceNoWrap"
        :for="id">
        <slot name="label">
          {{ element.label }}
        </slot>

        <span
          v-if="element.props.description"
          class="woocommerce-help-tip"
          :data-tip="translate(element.props.description)" />
      </label>
    </PdkTableCol>

    <PdkTableCol>
      <div>
        <slot />
      </div>
    </PdkTableCol>
  </PdkTableRow>
</template>

<script lang="ts">
import {ElementInstance, generateFieldId, usePdkConfig, useTranslate} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';

export default defineComponent({
  name: 'WcFormGroup',
  props: {
    element: {
      type: Object as PropType<ElementInstance>,
      required: true,
    },
  },

  setup: (props) => {
    return {
      id: generateFieldId(props.element),
      config: usePdkConfig(),
      translate: useTranslate(),
    };
  },
});
</script>
