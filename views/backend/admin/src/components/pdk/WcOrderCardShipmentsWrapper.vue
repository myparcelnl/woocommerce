<template>
  <div>
    <slot />

    <WcSpinner v-if="loading" />
  </div>
</template>

<script lang="ts">
import {PdkButtonAction, useLanguage} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';
import WcSpinner from '../WcSpinner.vue';

export default defineComponent({
  name: 'WcOrderCardShipmentsWrapper',
  components: {WcSpinner},

  props: {
    loading: {
      type: Boolean,
    },

    actions: {
      type: Array as PropType<PdkButtonAction[]>,
      default: () => [],
    },
  },

  setup: () => {
    const {translate} = useLanguage();

    return {
      translate,
    };
  },
});
</script>
