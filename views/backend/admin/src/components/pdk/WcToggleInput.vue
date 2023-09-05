<template>
  <input
    :id="id"
    :name="id"
    :value="transformedModel"
    type="hidden" />

  <a v-test="[AdminComponent.ToggleInput, element]">
    <input
      :id="`${id}-toggle`"
      v-model="model"
      :disabled="element.isDisabled || element.isSuspended || element.isReadOnly"
      :readonly="element.isReadOnly"
      :value="true"
      class="!mypa-hidden"
      tabindex="-1"
      type="checkbox"
      v-bind="$attrs" />

    <label
      :class="[
        `woocommerce-input-toggle--${model ? 'enabled' : 'disabled'}`,
        {
          'woocommerce-input-toggle--loading': element.isDisabled || element.isSuspended || element.isReadOnly,
        },
      ]"
      :for="`${id}-toggle`"
      class="!mypa-float-none !mypa-ml-auto !mypa-w-8 woocommerce-input-toggle"
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
import {computed, toRefs} from 'vue';
import {useVModel, get} from '@vueuse/core';
import {type ElementInstance, generateFieldId, useLanguage, AdminComponent} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: boolean; element: ElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: '1' | '0') => void>();
const propRefs = toRefs(props);

const model = useVModel(props, undefined, emit);

const transformedModel = computed(() => (get(model) ? '1' : '0'));

const id = generateFieldId(propRefs.element.value);

const {translate} = useLanguage();
</script>
