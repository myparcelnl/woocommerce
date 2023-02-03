<template>
  <PdkTableRow
    v-show="element.isVisible"
    v-test="{id}"
    valign="top">
    <PdkTableCol
      class="titledesc"
      component="th"
      scope="row">
      <label
        v-test="{type: 'label'}"
        :for="id">
        <slot name="label">
          {{ element.label }}
        </slot>

        <span
          v-if="element.props.description"
          v-test="{type: 'description'}"
          :data-tip="translate(element.props.description)"
          class="woocommerce-help-tip" />
      </label>
    </PdkTableCol>

    <PdkTableCol>
      <div class="mypa-max-w-md">
        <slot />
      </div>
    </PdkTableCol>
  </PdkTableRow>
</template>

<script lang="ts">
import {ElementInstance, generateFieldId, useLanguage, usePdkConfig} from '@myparcel-pdk/admin';
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
    const { translate } = useLanguage();

    return {
      id: generateFieldId(props.element),
      config: usePdkConfig(),
      translate,
    };
  },
});
</script>
