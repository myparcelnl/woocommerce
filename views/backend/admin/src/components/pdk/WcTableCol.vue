<template>
  <component
    :is="component"
    v-test>
    <slot />
  </component>
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'WcTableCol',
  props: {
    component: {
      type: String,
      default: 'td',
    },
  },
});
</script>
