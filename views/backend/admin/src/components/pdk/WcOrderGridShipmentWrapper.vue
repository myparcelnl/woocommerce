<template>
  <div>
    <slot />

    <PdkButtonGroup>
      <ActionButton
        v-for="(action, index) in actions"
        :key="`${index}_${action.id}`"
        :disabled="loading"
        :action="action" />
    </PdkButtonGroup>
  </div>
</template>

<script lang="ts">
import {ActionButton, PdkButtonAction, useLanguage} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';

export default defineComponent({
  name: 'WcOrderGridShipmentWrapper',

  components: {ActionButton},

  props: {
    loading: {
      type: Boolean,
    },

    actions: {
      type: Array as PropType<PdkButtonAction[]>,
      default: () => [],
    },
  },

  setup: () => {
    const {translate} = useLanguage();

    return {
      translate,
    };
  },
});
</script>
