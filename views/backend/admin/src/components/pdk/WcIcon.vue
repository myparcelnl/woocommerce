<template>
  <span
    v-if="dashIcon"
    v-test="'Icon'"
    :class="`dashicons-${dashIcon}`"
    class="dashicons mypa-h-auto mypa-w-auto"
    role="none" />
</template>

<script lang="ts" setup>
import {PropType, computed} from 'vue';
import {AdminIcon} from '@myparcel-pdk/admin/src';
import {createDashIcon} from '../../utils/createDashIcon';

const props = defineProps({
  icon: {
    type: String as PropType<AdminIcon>,
    required: true,
  },
});

const dashIcon = computed(() => createDashIcon(props.icon));
</script>
