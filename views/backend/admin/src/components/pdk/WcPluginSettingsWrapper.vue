<template>
  <div>
    <div class="woocommerce-layout">
      <div class="woocommerce-layout__header">
        <div class="woocommerce-layout__header-wrapper">
          <PdkHeading level="1">{{ title }}</PdkHeading>
        </div>
      </div>
    </div>

    <div class="woocommerce wrap">
      <slot />
    </div>
  </div>
</template>

<script lang="ts" setup>
import {useAppInfo} from '@myparcel-pdk/admin';

const {title} = useAppInfo();
</script>
