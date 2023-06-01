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
import {type PropType} from 'vue';
import {ActionButton, type ActionDefinition} from '@myparcel-pdk/admin';

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
