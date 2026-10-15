<template>
  <div
    v-test="AdminComponent.Notification"
    :class="[
      `notice-${notification.variant}`,
      {
        'is-dismissible': !notification.timeout,
      },
    ]"
    :aria-busy="notification.loading ? 'true' : undefined"
    class="mypa-relative notice"
    role="alert">
    <strong v-text="notification.title"></strong>
    <p
      v-for="(item, index) in contentArray"
      :key="`alert_${index}_${item}`"
      v-text="item" />

    <p
      v-if="notification.loading || notification.action"
      class="mypa-flex mypa-items-center mypa-gap-2">
      <WcSpinner v-if="notification.loading" />

      <button
        v-if="notification.action"
        class="button button-primary"
        type="button"
        @click="notification.action.onClick()"
        v-text="notification.action.label" />
    </p>
  </div>
</template>

<script lang="ts" setup>
import {type PropType, computed} from 'vue';
import {toArray} from '@myparcel-dev/ts-utils';
import {AdminComponent, type Notification} from '@myparcel-dev/pdk-admin';
import WcSpinner from '../WcSpinner.vue';

const props = defineProps({
  notification: {
    type: Object as PropType<Notification>,
    required: true,
  },
});

const contentArray = computed(() => toArray(props.notification.content));
</script>
