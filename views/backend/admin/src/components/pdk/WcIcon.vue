<template>
  <span
    v-test="'Icon'"
    :class="`dashicons-${mappedIcon}`"
    class="dashicons mypa-h-auto mypa-w-auto"
    role="none" />
</template>

<script lang="ts">
import {PropType, computed, defineComponent} from 'vue';
import {PdkIcon} from '@myparcel-pdk/admin/src';
import {pdkIconToDashIcon} from '../../utils/pdkIconToDashIcon';

export default defineComponent({
  name: 'WcIcon',

  props: {
    icon: {
      type: String as PropType<PdkIcon>,
      required: true,
    },
  },

  setup: (props) => {
    return {
      mappedIcon: computed(() => pdkIconToDashIcon(props.icon)),
    };
  },
});
</script>
