<template>
  <a
    v-test
    href="#"
    class="nav-tab"
    :data-test-id="`TabNavButton-${tab.name}`"
    :class="{
      'nav-tab-active': active,
    }"
    @click.prevent="$emit('click')">
    <PdkIcon
      v-if="tab.icon"
      :icon="tab.icon" />

    {{ translate(tab.label) }}
  </a>
</template>

<script lang="ts">
import {PdkTab, useLanguage} from '@myparcel/pdk-frontend';
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
