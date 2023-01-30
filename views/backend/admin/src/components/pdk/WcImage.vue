<template>
  <img
    v-test
    :src="src"
    :alt="alt" />
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'WcIcon',

  props: {
    alt: {
      type: String,
      required: true,
    },

    src: {
      type: String,
      required: true,
    },
  },
});
</script>
