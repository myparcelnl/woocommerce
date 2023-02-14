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

<script lang="ts" setup>
import {ActionButton, PdkAction} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';

defineProps({
  loading: {
    type: Boolean,
  },

  actions: {
    type: Array as PropType<PdkAction[]>,
    default: () => [],
  },
});
</script>
