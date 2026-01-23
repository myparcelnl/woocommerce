<template>
  <div
    v-test="AdminComponent.Table"
    class="mypa-overflow-scroll">
    <table class="striped widefat">
      <thead v-if="$slots.header">
        <slot name="header" />
      </thead>

      <TransitionGroup
        v-if="transitionName"
        :name="transitionName"
        class="mypa-relative"
        tag="tbody">
        <slot />
      </TransitionGroup>

      <tbody
        v-else
        class="mypa-relative">
        <slot />
      </tbody>

      <tfoot v-if="$slots.footer">
        <slot name="footer" />
      </tfoot>
    </table>
  </div>
</template>

<script lang="ts" setup>
import {computed} from 'vue';
import {useAdminConfig, AdminComponent} from '@myparcel-dev/pdk-admin';

const props = defineProps<{transition?: false | string}>();

const config = useAdminConfig();

const transitionName = computed(() => {
  return props.transition === false ? undefined : props.transition ?? config?.transitions?.tableRow;
});
</script>
