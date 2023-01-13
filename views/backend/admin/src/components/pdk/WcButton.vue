<template>
  <button
    type="button"
    class="button"
    :disabled="disabled"
    @click="$emit('click')">
    <span class="mypa-h-full mypa-inline-flex">
      <PdkIcon
        v-if="icon"
        class="mypa-mr-1 mypa-my-auto"
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
import {PdkIcon, useLanguage} from '@myparcel/pdk-frontend';
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
    const {translate} = useLanguage();

    return {
      translate,
    };
  },
});
</script>
