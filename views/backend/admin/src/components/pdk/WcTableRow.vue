<template>
  <component :is="component">
    <slot />
  </component>
</template>

<script lang="ts" setup>
defineProps({
  component: {
    type: String,
    default: 'tr',
  },
});
</script>
