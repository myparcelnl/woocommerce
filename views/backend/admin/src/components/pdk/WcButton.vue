<template>
  <button
    type="button"
    class="button"
    :disabled="loading || disabled"
    :class="{
      'mypa-animate-pulse': loading,
      'button-small': size === 'sm',
      'button-large': size === 'lg',
    }"
    @click="$emit('click')">
    <span class="mypa-h-full mypa-inline-flex">
      <PdkIcon
        v-if="icon"
        class="mypa-m-auto"
        :class="label || $slots.default ? 'mypa-mr-1' : null"
        :icon="icon" />

      <slot>
        <span class="mypa-mt-0.5">
          {{ translate(label) }}
        </span>
      </slot>
    </span>
  </button>
</template>

<script lang="ts">
import {PdkButtonSize, PdkIcon, useLanguage} from '@myparcel/pdk-frontend';
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
