<template>
  <table class="form-table">
    <thead v-if="$slots.header">
    <slot name="header" />
    </thead>

    <TransitionGroup
      :name="pdkConfig?.transitions.tableRow"
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
import {usePdkConfig} from '@myparcel/pdk-frontend';

export default defineComponent({
  name: 'Bootstrap4Table',
  setup: () => ({
    pdkConfig: usePdkConfig(),
  }),
});
</script>
