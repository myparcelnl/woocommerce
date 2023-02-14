<template>
  <span
    v-test="'Icon'"
    :class="`dashicons-${mappedIcon}`"
    class="dashicons mypa-h-auto mypa-w-auto"
    role="none" />
</template>

<script lang="ts" setup>
import {PropType, computed} from 'vue';
import {PdkIcon} from '@myparcel-pdk/frontend-core/src';
import {pdkIconToDashIcon} from '../../utils/pdkIconToDashIcon';

const props = defineProps({
  icon: {
    type: String as PropType<PdkIcon>,
    required: true,
  },
});

const mappedIcon = computed(() => pdkIconToDashIcon(props.icon));
</script>
