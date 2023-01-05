<template>
  <PdkTableRow valign="top">
    <PdkTableCol component="th" scope="row" class="titledesc">
      <label :class="config?.cssUtilities?.whitespaceNoWrap">
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
      <slot />
    </PdkTableCol>
  </PdkTableRow>
</template>

<script lang="ts">
import {PropType, UnwrapNestedRefs, defineComponent} from 'vue';
import {usePdkConfig, useTranslate} from '@myparcel/pdk-frontend';
import {InteractiveElementInstance} from '@myparcel-vfb/core';

export default defineComponent({
  name: 'WcFormGroup',
  props: {
    element: {
      type: Object as PropType<UnwrapNestedRefs<InteractiveElementInstance>>,
      required: true,
    },
  },

  setup: () => {
    return {
      config: usePdkConfig(),
      translate: useTranslate(),
    };
  },
});
</script>
