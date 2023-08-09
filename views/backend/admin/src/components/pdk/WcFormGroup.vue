<template>
  <PdkTableRow v-if="!isInteractiveElement">
    <!-- This row avoids issues with table-layout: fixed if the first element on the page has colspan="2" -->
    <PdkTableCol
      class="!mypa-p-0"
      component="th" />
    <PdkTableCol class="!mypa-p-0" />
  </PdkTableRow>

  <PdkTableRow
    v-show="element.isVisible"
    v-test="AdminComponent.FormGroup"
    valign="top">
    <template v-if="isInteractiveElement">
      <PdkTableCol
        class="titledesc"
        component="th"
        scope="row">
        <label :for="id">
          <slot name="label">
            {{ element.label }}
          </slot>

          <span
            v-if="has(element.props?.description)"
            :data-tip="translate(element.props.description)"
            class="woocommerce-help-tip" />
        </label>
      </PdkTableCol>

      <PdkTableCol>
        <div class="mypa-max-w-md">
          <slot />
        </div>

        <p
          v-if="has(element.props?.subtext)"
          class="description"
          v-html="translate(element.props.subtext)" />
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
import {computed, onMounted} from 'vue';
import {AdminComponent, type ElementInstance, generateFieldId, useLanguage} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: boolean; element: ElementInstance}>();

const id = generateFieldId(props.element);

const isInteractiveElement = computed(() => props.element.hasOwnProperty('ref'));

onMounted(() => {
  // Initialize WooCommerce tooltips/"tiptips"
  document.body.dispatchEvent(new Event('init_tooltips'));
});

const {translate, has} = useLanguage();
</script>
