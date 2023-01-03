<template>
  <WcTable>
    <div
      v-if="$slots.header"
      className="card-header">
      <slot name="header" />
    </div>
    <div className="card-body">
      <slot />
    </div>
    <div
      v-if="$slots.footer"
      className="card-footer d-flex">
      <slot name="footer" />
    </div>
  </WcTable>
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
