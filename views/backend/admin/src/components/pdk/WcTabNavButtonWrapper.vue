<template>
  <nav
    v-test="AdminComponent.TabNavButtonWrapper"
    class="nav-tab-wrapper woo-nav-tab-wrapper">
    <slot />
  </nav>
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-pdk/admin';
</script>
