<template>
  <component
    :is="component"
    v-test="'TableCol'">
    <slot />
  </component>
</template>

<script lang="ts" setup>
defineProps({
  component: {
    type: String,
    default: 'td',
  },
});
</script>
