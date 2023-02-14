<template>
  <table
    v-test="'Table'"
    class="striped widefat">
    <thead
      v-if="$slots.header"
      v-test="'Table__header'">
      <slot name="header" />
    </thead>

    <TransitionGroup
      v-test="'Table__body'"
      :name="config?.transitions.tableRow"
      tag="tbody">
      <slot />
    </TransitionGroup>

    <tfoot
      v-if="$slots.footer"
      v-test="'Table__footer'">
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
