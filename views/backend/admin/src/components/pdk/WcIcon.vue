<template>
  <span :title="icon"></span>
</template>

<script lang="ts">
import {PropType, defineComponent} from 'vue';
import {PdkIcon} from '@myparcel/pdk-frontend';

export default defineComponent({
  name: 'WcIcon',

  props: {
    icon: {
      type: String as PropType<PdkIcon>,
      required: true,
    },
  },
});
</script>
