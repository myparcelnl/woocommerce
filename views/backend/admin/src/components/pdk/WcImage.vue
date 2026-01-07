<template>
  <img
    v-test="AdminComponent.Image"
    :alt="alt"
    :src="src" />
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-dev/pdk-admin';

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
