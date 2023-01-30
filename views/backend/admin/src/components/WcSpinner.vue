<template>
  <div class="is-active spinner" v-test>
    <svg
      class="spinner__svg"
      viewBox="0 0 66 66"
      xmlns="http://www.w3.org/2000/svg">
      <circle
        class="spinner__path"
        fill="none"
        stroke-width="6"
        stroke-linecap="round"
        cx="33"
        cy="33"
        r="30" />
    </svg>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'WcSpinner',
});
</script>
