<template>
  <a
    v-test="AdminComponent.TabNavButton"
    :class="{
      'nav-tab-active': active,
    }"
    class="nav-tab"
    href="#"
    @click.prevent="$emit('click')">
    <slot />
  </a>
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-pdk/admin';

defineProps<{active: boolean}>();
defineEmits<(event: 'click') => void>();
</script>
