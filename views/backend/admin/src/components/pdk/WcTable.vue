<template>
  <table
    v-test
    class="striped widefat">
    <thead v-if="$slots.header">
      <slot name="header" />
    </thead>

    <TransitionGroup
      :name="config?.transitions.tableRow"
      tag="tbody">
      <slot />
    </TransitionGroup>

    <tfoot v-if="$slots.footer">
      <slot name="footer" />
    </tfoot>
  </table>
</template>

<script lang="ts">
import {defineComponent} from 'vue';
import {usePdkConfig} from '@myparcel-pdk/admin/src';

export default defineComponent({
  name: 'WcTable',
  setup: () => ({
    config: usePdkConfig(),
  }),
});
</script>
