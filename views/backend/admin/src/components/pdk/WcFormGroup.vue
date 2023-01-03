<template>
  <PdkTable>
    <tr>
      <th scope="row">
        <label :class="config?.cssUtilities?.whitespaceNoWrap">
          <slot name="label">
            {{ translate(label) }}
          </slot>

          <span
            v-if="description"
            class="woocommerce-help-tip"
            v-text="description" />
        </label>
      </th>
      <td>
        <p class="form-row">
          <span class="woocommerce-input-wrapper">
            <component
              :is="component"
              v-bind="{...$attrs, ...$props}" />
          </span>
        </p>
      </td>
    </tr>
  </PdkTable>
</template>

<script lang="ts">
import {PdkComponentName, usePdkConfig, useTranslate} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';
import WcTable from './WcTable.vue';

export default defineComponent({
  name: 'WcFormGroup',
  props: {
    /**
     * Label of the form group. Can be used instead of the label slot.
     */
    label: {
      type: String,
      default: null,
    },

    component: {
      type: String as PropType<PdkComponentName>,
      default: null,
    },

    description: {
      type: String,
      default: null,
    },
  },

  setup: () => ({
    config: usePdkConfig(),
    translate: useTranslate(),
  }),
});
</script>
