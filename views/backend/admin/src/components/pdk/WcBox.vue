<template>
  <div
    v-test="'Card'"
    class="">
    <div>
      <slot name="header">
        <PdkHeading level="3">
          {{ translate(title) }}
        </PdkHeading>
      </slot>
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
import {ActionButton, AnyAdminAction, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';

defineProps({
  loading: {
    type: Boolean,
  },

  title: {
    type: String,
    default: null,
  },

  actions: {
    type: Array as PropType<AnyAdminAction[]>,
    default: () => [],
  },
});

const {translate} = useLanguage();
</script>
