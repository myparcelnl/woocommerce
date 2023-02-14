<template>
  <img
    v-test="'Image'"
    :alt="alt"
    :src="src" />
</template>

<script lang="ts" setup>
defineProps({
  alt: {
    type: String,
    required: true,
  },

  src: {
    type: String,
    required: true,
  },
});
</script>
