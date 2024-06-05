<template>
  <div
    v-test="AdminComponent.Table"
    class="mypa-overflow-scroll">
    <table class="striped widefat">
      <thead v-if="$slots.header">
        <slot name="header" />
      </thead>

      <TransitionGroup
        :name="config?.transitions?.tableRow"
        class="mypa-relative"
        tag="tbody">
        <slot />
      </TransitionGroup>

      <tfoot v-if="$slots.footer">
        <slot name="footer" />
      </tfoot>
    </table>
  </div>
</template>

<script lang="ts" setup>
import {useAdminConfig, AdminComponent} from '@myparcel-pdk/admin';

const config = useAdminConfig();
</script>
