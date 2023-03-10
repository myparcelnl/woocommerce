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
import {ActionButton, ActionDefinition} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';

defineProps({
  actions: {
    type: Array as PropType<ActionDefinition[]>,
    default: () => [],
  },

  loading: {
    type: Boolean,
  },
});
</script>
