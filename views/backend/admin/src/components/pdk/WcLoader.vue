<template>
  <component :is="tag">
    <div class="mypa-animate-pulse mypa-gap-x-4 mypa-gap-y-2 mypa-grid mypa-max-w-sm mypa-mb-2">
      <div
        :class="classes"
        class="mypa-col-span-4" />
      <div
        :class="classes"
        class="mypa-col-span-5" />
      <div
        :class="classes"
        class="mypa-col-span-3" />
    </div>
  </component>
</template>

<script lang="ts" setup>
defineProps({
  tag: {
    type: String,
    default: 'div',
  },
});

const classes = ['mypa-bg-gray-300', 'mypa-rounded', 'mypa-h-4'];
</script>
