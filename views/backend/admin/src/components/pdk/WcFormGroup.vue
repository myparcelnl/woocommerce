<template>
  <PdkTableRow
    v-show="element.isVisible"
    v-test="'FormGroup'"
    valign="top">
    <PdkTableCol
      class="titledesc"
      component="th"
      scope="row">
      <label
        v-test="'FormGroup__label'"
        :for="id">
        <slot name="label">
          {{ element.label }}
        </slot>

        <span
          v-if="element.props.description"
          v-test="'FormGroup__description'"
          :data-tip="translate(element.props.description)"
          class="woocommerce-help-tip" />
      </label>
    </PdkTableCol>

    <PdkTableCol>
      <div
        v-test="'FormGroup__slot'"
        class="mypa-max-w-md">
        <slot />
      </div>
    </PdkTableCol>
  </PdkTableRow>
</template>

<script lang="ts" setup>
import {ElementInstance, generateFieldId, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';

const props = defineProps({
  element: {
    type: Object as PropType<ElementInstance>,
    required: true,
  },
});

const {translate} = useLanguage();

const id = generateFieldId(props.element);
</script>
