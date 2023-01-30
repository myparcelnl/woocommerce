<template>
  <component
    :is="component"
    v-test
    data-test-id="TableRow">
    <slot />
  </component>
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'WcTableRow',
  props: {
    component: {
      type: String,
      default: 'tr',
    },
  },
});
</script>
