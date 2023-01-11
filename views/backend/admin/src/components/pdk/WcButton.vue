<template>
  <button
    type="button"
    class="button"
    :disabled="disabled"
    @click="$emit('click')">
    <PdkIcon
      v-if="icon"
      :icon="icon" />
    <slot>
      {{ translate(label) }}
    </slot>
  </button>
</template>

<script lang="ts">
import {PdkIcon, useTranslate} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';

export default defineComponent({
  name: 'WcButton',
  props: {
    disabled: {
      type: Boolean,
    },

    label: {
      type: String,
      default: null,
    },

    icon: {
      type: String as PropType<PdkIcon>,
      default: null,
    },
  },

  emits: ['click'],

  setup: () => {
    return {
      translate: useTranslate(),
    };
  },
});
</script>
