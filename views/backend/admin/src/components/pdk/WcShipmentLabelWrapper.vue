<template>
  <div class="mypa-border mypa-border-gray-300 mypa-border-solid mypa-mb-1 mypa-p-1 mypa-relative mypa-rounded">
    <WcLoadingOverlay v-show="loading" />

    <slot />
  </div>
</template>

<script lang="ts" setup>
import WcLoadingOverlay from '../WcLoadingOverlay.vue';

defineProps({
  loading: {
    type: Boolean,
  },
});
</script>
