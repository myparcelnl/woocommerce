<template>
  <PdkTable>
    <div
      v-if="$slots.header"
      class="card-header">
      <slot name="header" />
    </div>
    <div class="card-body">
      <slot />
    </div>
    <div
      v-if="$slots.footer"
      class="card-footer d-flex">
      <slot name="footer" />
    </div>
  </PdkTable>
</template>

<script lang="ts">
import {defineComponent} from 'vue';
import WcTable from './WcTable.vue';

export default defineComponent({
  name: 'WcCard',

  props: {
    loading: {
      type: Boolean,
    },
  },
});
</script>
