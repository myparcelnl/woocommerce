<template>
  <span
    v-if="has(element.props?.description)"
    :data-tip="translate(element.props.description)"
    class="woocommerce-help-tip" />
</template>

<script lang="ts" setup>
import {onMounted} from 'vue';
import {useLanguage, type ElementInstance} from '@myparcel-pdk/admin';

defineProps<{element: ElementInstance}>();

const {has, translate} = useLanguage();

onMounted(() => {
  document.body.dispatchEvent(new Event('init_tooltips'));
});
</script>
