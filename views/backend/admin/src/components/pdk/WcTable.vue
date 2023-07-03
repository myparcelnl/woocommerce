<template>
  <table
    v-test="AdminComponent.Table"
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

<script lang="ts" setup>
import {useAdminConfig, AdminComponent} from '@myparcel-pdk/admin';

const config = useAdminConfig();
</script>
