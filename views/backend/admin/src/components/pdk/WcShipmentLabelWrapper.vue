<template>
  <div>
    <slot />
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue';
import {useLanguage} from '@myparcel/pdk-frontend';

export default defineComponent({
  name: 'WcShipmentLabelWrapper',

  props: {
    loading: {
      type: Boolean,
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
