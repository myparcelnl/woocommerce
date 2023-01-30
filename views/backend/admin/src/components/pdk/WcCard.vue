<template>
  <div
    v-test
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

    <div class="d-flex">
      <slot name="footer">
        <PdkButtonGroup>
          <ActionButton
            v-for="(action, index) in actions"
            :key="`${index}_${action.id}`"
            :disabled="loading"
            :action="action" />
        </PdkButtonGroup>
      </slot>
    </div>
  </div>
</template>

<script lang="ts">
import {ActionButton, PdkAction, useLanguage} from '@myparcel/pdk-frontend';
import {PropType, defineComponent} from 'vue';

export default defineComponent({
  name: 'WcCard',

  components: {ActionButton},

  props: {
    loading: {
      type: Boolean,
    },

    title: {
      type: String,
      default: null,
    },

    actions: {
      type: Array as PropType<PdkAction[]>,
      default: () => [],
    },
  },

  setup: () => {
    const {translate} = useLanguage();

    return {
      translate,
    };
  },
});
</script>
