<template>
  <div
    v-test="AdminComponent.Row"
    :class="{
      'mypa-gap-4': !collapseGutters,
    }"
    class="mypa-cols-auto mypa-grid mypa-grid-flow-col">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-dev/pdk-admin';

defineProps({
  collapseGutters: {
    type: Boolean,
  },
  // eslint-disable-next-line vue/no-unused-properties
  columns: {
    type: [Number, String],
    default: null,
  },
});
</script>
