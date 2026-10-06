<template>
  <img
    v-test="AdminComponent.Image"
    v-bind="$props" />
</template>

<script lang="ts" setup>
/* eslint-disable vue/no-unused-properties */
import {AdminComponent, type ImageProps} from '@myparcel-dev/pdk-admin';

defineProps<ImageProps>();
</script>
