<template>
  <component
    :is="component"
    v-test="AdminComponent.TableCol">
    <slot />
  </component>
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-pdk/admin';

defineProps({
  component: {
    type: String,
    default: 'td',
  },
});
</script>
