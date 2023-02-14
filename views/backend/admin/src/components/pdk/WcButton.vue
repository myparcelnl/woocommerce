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

<script lang="ts">
import {PdkButtonSize, PdkIcon, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType, defineComponent} from 'vue';

export default defineComponent({
  name: 'WcButton',
  props: {
    disabled: {
      type: Boolean,
    },

    icon: {
      type: String as PropType<PdkIcon>,
      default: null,
    },

    size: {
      type: String as PropType<PdkButtonSize>,
      default: 'md',
    },

    label: {
      type: String,
      default: null,
    },

    loading: {
      type: Boolean,
    },
  },

  emits: ['click'],

  setup: () => {
    const {translate} = useLanguage();

    return {
      translate,
    };
  },
});
</script>
