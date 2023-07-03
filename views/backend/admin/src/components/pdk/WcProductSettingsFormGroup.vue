<template>
  <div
    v-test="AdminComponent.FormGroup"
    class="options_group">
    <p class="form-field">
      <label :for="id">
        <slot name="label">
          {{ element.label }}
        </slot>
      </label>

      <slot />

      <span
        v-if="has(element.props?.description)"
        :data-tip="translate(element.props.description)"
        class="woocommerce-help-tip" />

      <span
        v-if="has(element.props?.subtext)"
        class="description"
        v-html="translate(element.props.subtext)" />
    </p>
  </div>
</template>

<script lang="ts" setup>
import {onMounted} from 'vue';
import {AdminComponent, type ElementInstance, generateFieldId, useLanguage} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: boolean; element: ElementInstance}>();

const id = generateFieldId(props.element);

onMounted(() => {
  // Initialize WooCommerce tooltips/"tiptips"
  document.body.dispatchEvent(new Event('init_tooltips'));
});

const {translate, has} = useLanguage();
</script>
