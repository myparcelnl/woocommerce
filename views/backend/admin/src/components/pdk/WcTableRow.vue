<template>
  <component
    :is="component"
    v-test
    >
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
