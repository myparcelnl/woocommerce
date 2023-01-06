<template>
  <button
    type="button"
    class="button-primary"
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
      default: 'save',
    },

    icon: {
      type: String as PropType<PdkIcon>,
      default: null,
    },
  },

  emits: ['click'],

  setup: (props) => {
    return {
      translate: useTranslate(),
    };
  },
});
</script>
