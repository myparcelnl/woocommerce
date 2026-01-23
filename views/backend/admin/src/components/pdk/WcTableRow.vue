<template>
  <component
    :is="component"
    v-test="AdminComponent.TableRow">
    <slot />
  </component>
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-dev/pdk-admin';

defineProps({
  component: {
    type: String,
    default: 'tr',
  },
});
</script>
