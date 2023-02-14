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

<script lang="ts">
import {PdkTab, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType, defineComponent} from 'vue';

export default defineComponent({
  name: 'WcTabNavButton',
  props: {
    active: {
      type: Boolean,
    },

    tab: {
      type: Object as PropType<PdkTab>,
      required: true,
    },
  },

  emits: ['click'],

  setup: () => {
    const {translate} = useLanguage();

    return {
      translate,
    };
  },
});
</script>
