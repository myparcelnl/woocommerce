<template>
  <div>
    <h1>Test</h1>
    <p>{{ testQuery.data }}</p>
  </div>
</template>

<script lang="ts">
import {useQuery} from '@tanstack/vue-query';
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'App',

  setup: () => {
    const testQuery = useQuery(['test'], () => Promise.resolve('test'), {
      staleTime: Infinity,
    });

    console.log(testQuery.data);

    return {testQuery};
  },
});
</script>
