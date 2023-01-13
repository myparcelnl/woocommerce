<template>
  <span
    class="dashicons"
    :class="`dashicons-${mappedIcon}`"></span>
</template>

<script lang="ts">
import {PropType, computed, defineComponent} from 'vue';
import {PdkIcon} from '@myparcel/pdk-frontend';
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
