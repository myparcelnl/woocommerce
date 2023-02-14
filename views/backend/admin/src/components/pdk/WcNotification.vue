<template>
  <div
    v-test="'Notification'"
    :class="[
      `notice-${notification.variant}`,
      {
        'is-dismissible': !notification.timeout,
      },
    ]"
    class="mypa-relative notice"
    role="alert">
    <strong v-text="notification.title"></strong>
    <p
      v-for="(item, index) in contentArray"
      :key="`alert_${index}_${item}`"
      v-text="item" />
  </div>
</template>

<script lang="ts" setup>
import {PropType, computed} from 'vue';
import {PdkNotification} from '@myparcel-pdk/admin/src';
import {toArray} from '@myparcel/ts-utils';

const props = defineProps({
  notification: {
    type: Object as PropType<PdkNotification>,
    required: true,
  },
});

const contentArray = computed(() => toArray(props.notification.content));
</script>
