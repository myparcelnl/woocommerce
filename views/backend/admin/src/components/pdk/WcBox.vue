<template>
  <div
    v-test="AdminComponent.Box"
    class="mypa-relative postbox">
    <WcLoadingOverlay v-show="loading" />

    <div
      v-if="$slots.header || title"
      class="postbox-header">
      <PdkHeading
        class="hndle"
        level="3">
        <slot name="header">
          {{ translate(title) }}
        </slot>
      </PdkHeading>
    </div>

    <div class="inside">
      <slot />
    </div>

    <div
      v-if="$slots.footer || actions?.length"
      class="d-flex mypa-pb-3 mypa-px-3">
      <!-- Box footer. -->
      <slot name="footer">
        <PdkButtonGroup v-if="actions?.length">
          <ActionButton
            v-for="action in actions"
            :key="action.id"
            :action="action" />
        </PdkButtonGroup>
      </slot>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {type PropType} from 'vue';
import {ActionButton, AdminComponent, type ActionDefinition, Size, useLanguage} from '@myparcel-pdk/admin';
import WcLoadingOverlay from '../WcLoadingOverlay.vue';

defineProps({
  actions: {
    type: Array as PropType<ActionDefinition[]>,
    default: () => [],
  },

  loading: {
    type: Boolean,
  },

  size: {
    type: String as PropType<Size>,
    default: Size.Medium,
  },

  title: {
    type: String,
    default: null,
  },
});

const {translate} = useLanguage();
</script>
