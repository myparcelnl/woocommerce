<template>
  <div
    v-test="'Box'"
    :class="cssClasses"
    class="mypa-border mypa-border-gray-300 mypa-border-solid mypa-relative">
    <WcLoadingOverlay v-show="loading" />

    <div v-if="$slots.header || title">
      <PdkHeading level="3">
        <slot name="header">
          {{ translate(title) }}
        </slot>
      </PdkHeading>
    </div>

    <div>
      <slot />
    </div>

    <div
      v-if="$slots.footer || actions?.length"
      class="d-flex">
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
import {ActionButton, ActionDefinition, Size, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType, computed} from 'vue';
import WcLoadingOverlay from '../WcLoadingOverlay.vue';

const props = defineProps({
  actions: {
    type: Array as PropType<ActionDefinition[]>,
    default: () => [],
  },

  loading: {
    type: Boolean,
  },

  size: {
    type: String as PropType<Size>,
    default: Size.MEDIUM,
  },

  title: {
    type: String,
    default: null,
  },
});

const cssClasses = computed(() => ({
  'mypa-px-2 mypa-pb-2 mypa-pt-1 mypa-mb-1 mypa-rounded': [Size.SMALL, Size.EXTRA_SMALL].includes(props.size),
  'mypa-px-4 mypa-pb-4 mypa-pt-3 mypa-mb-3': [Size.MEDIUM].includes(props.size),
  'mypa-px-5 mypa-pb-5 mypa-pt-4 mypa-mb-4': [Size.LARGE].includes(props.size),
  'mypa-px-6 mypa-pb-6 mypa-pt-5 mypa-mb-5': [Size.EXTRA_LARGE].includes(props.size),
  'mypa-rounded-xl': [Size.MEDIUM, Size.LARGE, Size.EXTRA_LARGE].includes(props.size),
}));

const {translate} = useLanguage();
</script>
