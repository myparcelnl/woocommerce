<template>
  <div>
    <div v-if="$slots.header">
      <slot name="header">
        {{ translate(title) }}
      </slot>
    </div>

    <div>
      <slot />
    </div>

    <div
      v-if="$slots.footer"
      class="d-flex">
      <slot name="footer">
        <ActionButton
          v-for="(action, index) in actions"
          :key="`${index}_${action.id}`"
          :disabled="loading"
          :action="action" />
      </slot>
    </div>
  </div>
</template>

<script lang="ts">
import {ActionButton, PdkButtonAction, useTranslate} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';

export default defineComponent({
  name: 'WcCard',

  components: {ActionButton},

  props: {
    loading: {
      type: Boolean,
    },

    title: {
      type: String,
      default: null,
    },

    /**
     * Available actions on the card.
     */
    actions: {
      type: Array as PropType<PdkButtonAction[]>,
      default: () => [],
    },
  },

  setup: () => {
    return {
      translate: useTranslate(),
    };
  },
});
</script>
