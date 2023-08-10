<template>
  <PdkBox
    v-test="AdminComponent.ShipmentLabelWrapper"
    :size="Size.Small"
    class="mypa-mb-1 mypa-min-w-0">
    <WcLoadingOverlay v-show="loading" />

    <slot />
  </PdkBox>
</template>

<script lang="ts" setup>
import {AdminComponent, Size} from '@myparcel-pdk/admin';
import WcLoadingOverlay from '../WcLoadingOverlay.vue';

defineProps({
  loading: {
    type: Boolean,
  },
});
</script>
