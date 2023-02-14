<template>
  <img
    v-test="'Image'"
    :alt="alt"
    :src="src" />
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
