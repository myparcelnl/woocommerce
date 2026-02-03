<template>
  <div
    v-test="AdminComponent.Notification"
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
import {type PropType, computed} from 'vue';
import {AdminComponent, type Notification} from '@myparcel-dev/pdk-admin';
import {toArray} from '@myparcel-dev/ts-utils';

const props = defineProps({
  notification: {
    type: Object as PropType<Notification>,
    required: true,
  },
});

const contentArray = computed(() => toArray(props.notification.content));
</script>
