<template>
  <div>
    <slot />

    <PdkButtonGroup>
      <ActionButton
        v-for="(action, index) in actions"
        :key="`${index}_${action.id}`"
        :action="action"
        :disabled="loading" />
    </PdkButtonGroup>
  </div>
</template>

<script lang="ts">
import {ActionButton, PdkAction, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType, defineComponent} from 'vue';

export default defineComponent({
  name: 'WcOrderGridShipmentWrapper',

  components: {ActionButton},

  props: {
    loading: {
      type: Boolean,
    },

    actions: {
      type: Array as PropType<PdkAction[]>,
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
