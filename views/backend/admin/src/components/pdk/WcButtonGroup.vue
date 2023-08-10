<template>
  <div
    v-test="AdminComponent.ButtonGroup"
    class="mypa-flex mypa-flex-wrap mypa-gap-1">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-pdk/admin';
</script>
