<template>
  <div>
    <div class="woocommerce-layout">
      <div class="woocommerce-layout__header">
        <div class="woocommerce-layout__header-wrapper">
          <PdkHeading level="1">MyParcel</PdkHeading>
        </div>
      </div>
    </div>

    <div class="woocommerce wrap">
      <slot />
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue';

export default defineComponent({
  name: 'WcPluginSettingsWrapper',
});
</script>
