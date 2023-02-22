<template>
  <div
    :class="{
      'mypa-gap-4': !collapseGutters,
    }"
    class="mypa-cols-auto mypa-grid mypa-grid-flow-col">
    <slot />
  </div>
</template>

<script lang="ts" setup>
defineProps({
  collapseGutters: {
    type: Boolean,
  },
  columns: {
    type: [Number, String],
    default: null,
  },
});
</script>
