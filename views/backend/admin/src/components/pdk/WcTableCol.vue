<template>
  <component
    :is="component"
    v-test="AdminComponent.TableCol"
    class="mypa-table-cell">
    <slot />
  </component>
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-dev/pdk-admin';

defineProps({
  component: {
    type: String,
    default: 'td',
  },
});
</script>
