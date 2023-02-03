<template>
  <div
    v-test
    class="is-active spinner">
    <svg
      class="spinner__svg"
      viewBox="0 0 66 66"
      xmlns="http://www.w3.org/2000/svg">
      <circle
        class="spinner__path"
        cx="33"
        cy="33"
        fill="none"
        r="30"
        stroke-linecap="round"
        stroke-width="6" />
    </svg>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'WcSpinner',
});
</script>
