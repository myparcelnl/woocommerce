<template>
  <div>
    <slot />
  </div>
</template>

<script lang="ts" setup>
defineProps({
  span: {
    type: Number,
    default: null,
  },
});
</script>
