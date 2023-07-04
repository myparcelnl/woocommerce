<template>
  <a v-test="AdminComponent.ToggleInput">
    <input
      :id="id"
      v-model="model"
      :name="id"
      :disabled="element.isDisabled || element.isSuspended || element.isReadOnly"
      :readonly="element.isReadOnly"
      :value="true"
      class="!mypa-hidden"
      tabindex="-1"
      v-bind="$attrs"
      type="checkbox" />

    <label
      class="!mypa-float-none !mypa-ml-auto !mypa-w-8 woocommerce-input-toggle"
      :class="[
        `woocommerce-input-toggle--${model ? 'enabled' : 'disabled'}`,
        {
          'woocommerce-input-toggle--loading': element.isDisabled || element.isSuspended || element.isReadOnly,
        },
      ]"
      :for="id"
      role="switch"
      tabindex="0">
      {{ translate(`toggle_${model ? 'yes' : 'no'}`) }}
    </label>
  </a>
</template>

<script lang="ts">
export default {inheritAttrs: false};
</script>

<script lang="ts" setup>
import {useVModel} from '@vueuse/core';
import {type ElementInstance, generateFieldId, useLanguage, AdminComponent} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: boolean; element: ElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: boolean) => void>();

const model = useVModel(props, undefined, emit);

const id = generateFieldId(props.element);

const {translate} = useLanguage();
</script>
