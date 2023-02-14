<template>
  <a
    v-test="'TabNavButton'"
    :class="{
      'nav-tab-active': active,
    }"
    class="nav-tab"
    href="#"
    @click.prevent="$emit('click')">
    <PdkIcon
      v-if="tab.icon"
      v-test="'TabNavButton__icon'"
      :icon="tab.icon" />

    {{ translate(tab.label) }}
  </a>
</template>

<script lang="ts" setup>
import {TabDefinition, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';

defineProps({
  active: {
    type: Boolean,
  },

  tab: {
    type: Object as PropType<TabDefinition>,
    required: true,
  },
});

defineEmits(['click']);

const {translate} = useLanguage();
</script>
