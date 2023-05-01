<template>
  <PdkTableRow
    v-show="element.isVisible"
    v-test="'FormGroup'"
    valign="top">
    <template v-if="isInteractiveElement">
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
            v-if="element.props?.description"
            v-test="'FormGroup__description'"
            :data-tip="translate(element.props?.description)"
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
    </template>

    <PdkTableCol
      v-else
      class="!mypa-p-0 mypa-border-b mypa-border-gray-500"
      colspan="2">
      <slot />
    </PdkTableCol>
  </PdkTableRow>
</template>

<script lang="ts" setup>
import {ElementInstance, generateFieldId, useLanguage} from '@myparcel-pdk/admin/src';
import {computed, onMounted} from 'vue';
import {InteractiveElementInstance} from '@myparcel/vue-form-builder/src';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: boolean; element: InteractiveElementInstance}>();

const id = generateFieldId(props.element as ElementInstance);

const {translate} = useLanguage();

const isInteractiveElement = computed(() => props.element.hasOwnProperty('ref'));

onMounted(() => {
  // Initialize WooCommerce tooltips/"tiptips"
  document.body.dispatchEvent(new Event('init_tooltips'));
});
</script>
