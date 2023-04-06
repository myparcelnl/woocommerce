<template>
  <div :class="config.cssUtilities?.displayFlex">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import {useAdminConfig} from '@myparcel-pdk/admin/src';

defineProps({
  // eslint-disable-next-line vue/no-unused-properties
  span: {
    type: Number,
    default: null,
  },
});

const config = useAdminConfig();
</script>
