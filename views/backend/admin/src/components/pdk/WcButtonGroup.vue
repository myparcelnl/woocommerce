<template>
  <div
    v-test="AdminComponent.ButtonGroup"
    class="mypa-cols-auto mypa-gap-1 mypa-grid-flow-col mypa-inline-grid">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-pdk/admin';
</script>
