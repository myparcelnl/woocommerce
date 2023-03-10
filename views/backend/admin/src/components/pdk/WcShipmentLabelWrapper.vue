<template>
  <PdkBox :size="Size.Small">
    <WcLoadingOverlay v-show="loading" />

    <slot />
  </PdkBox>
</template>

<script lang="ts" setup>
import {Size} from '@myparcel-pdk/admin/src';
import WcLoadingOverlay from '../WcLoadingOverlay.vue';

defineProps({
  loading: {
    type: Boolean,
  },
});
</script>
