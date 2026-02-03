<template>
  <span
    v-if="has(element.props?.subtext)"
    class="description"
    v-html="translate(element.props.subtext)" />
</template>

<script lang="ts" setup>
import {useLanguage, type ElementInstance} from '@myparcel-dev/pdk-admin';

defineProps<{element: ElementInstance}>();

const {has, translate} = useLanguage();
</script>
