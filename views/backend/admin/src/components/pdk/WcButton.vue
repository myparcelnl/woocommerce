<template>
  <button
    v-test="AdminComponent.Button"
    :class="[
      sizeClasses,
      variantClasses,
      {
        'button-disabled': disabled,
        'mypa-animate-pulse': loading,
        'mypa-opacity-50': loading || disabled,
      },
    ]"
    :disabled="loading || disabled"
    class="button"
    type="button"
    @click="$emit('click')">
    <span class="mypa-h-full mypa-inline-flex">
      <PdkIcon
        v-if="icon"
        :class="label ? 'mypa-mr-1' : null"
        :icon="icon"
        class="mypa-m-auto mypa-text-sm" />

      <slot>
        <span class="mypa-mt-0.5">
          {{ translate(label) }}
        </span>
      </slot>

      <WcSpinner
        v-show="loading"
        class="mypa-m-auto mypa-ml-1" />
    </span>
  </button>
</template>

<script lang="ts" setup>
import {type PropType, computed} from 'vue';
import {Variant, AdminComponent, type AdminIcon, Size, useLanguage} from '@myparcel-pdk/admin';
import WcSpinner from '../WcSpinner.vue';

const props = defineProps({
  disabled: {
    type: Boolean,
  },

  icon: {
    type: String as PropType<AdminIcon>,
    default: null,
  },

  label: {
    type: String,
    default: null,
  },

  loading: {
    type: Boolean,
  },

  size: {
    type: String as PropType<Size>,
    default: 'md',
  },

  variant: {
    type: String as PropType<Variant>,
    default: Variant.Primary,
  },
});

defineEmits(['click']);

const {translate} = useLanguage();

const sizeClasses = computed((): string[] => {
  return [
    ...(props.size === Size.ExtraSmall ? ['!mypa-min-h-0', '!mypa-leading-normal', '!mypa-px-1'] : []),
    ...([Size.Small, Size.ExtraSmall].includes(props.size) ? ['button-small'] : []),
    ...([Size.Large, Size.ExtraLarge].includes(props.size) ? ['button-large'] : []),
  ];
});

const variantClasses = computed((): string[] => {
  return [
    ...(Variant.Secondary === props.variant ? ['button-primary'] : []),
    ...(Variant.Error === props.variant ? ['button-link-delete'] : []),
  ];
});
</script>
