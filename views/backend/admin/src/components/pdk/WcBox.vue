<template>
  <div
    v-test="'Box'"
    class="mypa-relative">
    <WcLoadingOverlay v-show="loading" />

    <div v-if="$slots.header">
      <PdkHeading level="3">
        <slot name="header" />
      </PdkHeading>
    </div>

    <div>
      <slot />
    </div>

    <div
      v-if="actions.length || $slots.footer"
      class="d-flex">
      <slot name="footer">
        <PdkButtonGroup>
          <ActionButton
            v-for="(action, index) in actions"
            :key="`${index}_${action.id}`"
            :action="action"
            :disabled="loading" />
        </PdkButtonGroup>
      </slot>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ActionButton, AnyAdminAction} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';
import WcLoadingOverlay from '../WcLoadingOverlay.vue';

defineProps({
  loading: {
    type: Boolean,
  },

  actions: {
    type: Array as PropType<AnyAdminAction[]>,
    default: () => [],
  },
});
</script>
