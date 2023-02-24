<template>
  <input
    :id="id"
    v-model.trim="model"
    v-test="'TextInput'"
    :class="{
      'form-required': !element.isValid,
    }"
    :disabled="element.isDisabled || element.isSuspended"
    :type="element.props.type ?? 'text'"
    class="!mypa-w-full" />
</template>

<script lang="ts" setup>
import {generateFieldId, useElement} from '@myparcel-pdk/admin/src';
import {useVModel} from '@vueuse/core';

const props = defineProps({
  // eslint-disable-next-line vue/no-unused-properties
  modelValue: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const model = useVModel(props, undefined, emit);
const id = generateFieldId();
const element = useElement();
</script>
