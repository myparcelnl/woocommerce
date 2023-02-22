<template>
  <a v-test="'ToggleInput'">
    <input
      :id="id"
      v-model="model"
      :disabled="element.isDisabled || element.isSuspended"
      :value="true"
      class="!mypa-hidden"
      tabindex="-1"
      type="checkbox" />

    <label
      :class="`woocommerce-input-toggle woocommerce-input-toggle--${model ? 'enabled' : 'disabled'}`"
      :for="id"
      class="!mypa-float-none !mypa-ml-auto !mypa-w-8"
      role="switch"
      tabindex="0">
      {{ translate(`toggle_${model ? 'yes' : 'no'}`) }}
    </label>
  </a>
</template>

<script lang="ts" setup>
import {generateFieldId, useElement, useLanguage} from '@myparcel-pdk/admin/src';
import {useVModel} from '@vueuse/core';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps({modelValue: {type: Boolean}});
const emit = defineEmits(['update:modelValue']);

const model = useVModel(props, undefined, emit);

const id = generateFieldId();
const element = useElement();

const {translate} = useLanguage();
</script>
