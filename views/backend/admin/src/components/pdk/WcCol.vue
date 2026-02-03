<template>
  <div
    v-test="AdminComponent.Col"
    :class="config.cssUtilities?.displayFlex">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import {AdminComponent, useAdminConfig} from '@myparcel-dev/pdk-admin';

defineProps({
  // eslint-disable-next-line vue/no-unused-properties
  span: {
    type: Number,
    default: null,
  },
});

const config = useAdminConfig();
</script>
