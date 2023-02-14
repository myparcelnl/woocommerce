<template>
  <button
    :class="{
      'mypa-animate-pulse': loading,
      'button-small': size === 'sm',
      'button-large': size === 'lg',
    }"
    :disabled="loading || disabled"
    class="button"
    type="button"
    @click="$emit('click')">
    <span class="mypa-h-full mypa-inline-flex">
      <PdkIcon
        v-if="icon"
        :class="label ? 'mypa-mr-1' : null"
        :icon="icon"
        class="mypa-m-auto mypa-text-sm" />

      <slot>
        <span
          v-test="'content'"
          class="mypa-mt-0.5">
          {{ translate(label) }}
        </span>
      </slot>
    </span>
  </button>
</template>

<script lang="ts" setup>
import {AdminIcon, Size, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';

defineProps({
  disabled: {
    type: Boolean,
  },

  icon: {
    type: String as PropType<AdminIcon>,
    default: null,
  },

  size: {
    type: String as PropType<Size>,
    default: 'md',
  },

  label: {
    type: String,
    default: null,
  },

  loading: {
    type: Boolean,
  },
});

defineEmits(['click']);

const {translate} = useLanguage();
</script>
