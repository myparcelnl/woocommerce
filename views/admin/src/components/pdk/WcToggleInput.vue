<template>
  <a class="wcmp__toggle wcmp__d--inline-block">
    <input
      v-show=""
      type="hidden"
      :name="`myparcel_settings_${name}`"
      :value="value"
      data-type="toggle"
      :option_id="`myparcel_settings_${name}`"
      data-toggled="false">
    <span class="woocommerce-input-toggle" :class="value ? 'woocommerce-input-toggle--enabled' : 'woocommerce-input-toggle--disabled'">{{ value }}</span>
  </a>
</template>

<script>
export default {
  name: 'WcToggleInput',
  props: {
    disabled: {
      type: Boolean,
    },

    name: {
      type: String,
      required: true,
      default: '',
    },

    value: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
