<template>
  <div
    :class="`notice notice-${notification.variant}`"
    style="padding:12px 12px">
    <strong>{{ notification.title }}</strong>
    <p
      v-for="(item, index) in contentArray"
      :key="`alert_${index}_${item}`"
      v-text="item" />
  </div>
</template>

<script lang="ts">
import {toArray} from '@myparcel/ts-utils';
import {PdkNotification,usePdkConfig,useTranslate} from '@myparcel/pdk-frontend';
import {computed, defineComponent, PropType} from 'vue';

export default defineComponent({
  name: 'WcNotification',
  props: {
    notification: {
      type: Object as PropType<PdkNotification>,
      required: true,
    },
  },

  setup: (props) => {
    return {
      config: usePdkConfig(),
      translate: useTranslate(),
      contentArray: computed(() => {
        return toArray(props.notification.content);
      }),
    };
  },

});
</script>

