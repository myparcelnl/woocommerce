<template>
  <div
    v-test
    :class="[
      `notice-${notification.variant}`,
      {
        'is-dismissible': !notification.timeout,
      },
    ]"
    class="mypa-relative notice">
    <strong v-text="notification.title"></strong>
    <p
      v-for="(item, index) in contentArray"
      :key="`alert_${index}_${item}`"
      v-text="item" />
  </div>
</template>

<script lang="ts">
import {PdkNotification, useLanguage, usePdkConfig} from '@myparcel-pdk/admin/src';
import {PropType, computed, defineComponent} from 'vue';
import {toArray} from '@myparcel/ts-utils';

export default defineComponent({
  name: 'WcNotification',
  props: {
    notification: {
      type: Object as PropType<PdkNotification>,
      required: true,
    },
  },

  setup: (props) => {
    const {translate} = useLanguage();

    return {
      config: usePdkConfig(),
      contentArray: computed(() => toArray(props.notification.content)),
      translate,
    };
  },
});
</script>
