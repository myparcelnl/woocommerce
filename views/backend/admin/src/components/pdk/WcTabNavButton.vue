<template>
  <a
    v-test="AdminComponent.TabNavButton"
    :class="{
      'nav-tab-active': active,
    }"
    role="tab"
    :aria-selected="active"
    class="nav-tab"
    href="#"
    @click.prevent="$emit('click')">
    <slot />
  </a>
</template>

<script lang="ts" setup>
import {AdminComponent} from '@myparcel-dev/pdk-admin';

defineProps<{active: boolean}>();
defineEmits<(event: 'click') => void>();
</script>
