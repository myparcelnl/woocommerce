<template>
  <select
    :id="id"
    ref="selectElement"
    v-test="AdminComponent.SelectInput"
    :name="id" />
</template>

<script lang="ts" setup>
import {onBeforeUnmount, onMounted, ref, watchEffect} from 'vue';
import {get} from '@vueuse/core';
import {type ElementInstance, type OptionsProp, useSelectInputContext, AdminComponent} from '@myparcel-pdk/admin';
import {type OneOrMore} from '@myparcel/ts-utils';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{element: ElementInstance<OptionsProp>; modelValue: string | number}>();
const emit = defineEmits<(e: 'update:modelValue', value: OneOrMore<string | number>) => void>();

const {id, options} = useSelectInputContext(props, emit);

const selectElement = ref<HTMLElement | null>(null);
const $select = ref<JQuery | null>(null);

watchEffect(() => {
  $select.value?.attr('disabled', get(props.element.isDisabled) || get(props.element.isSuspended));
});

watchEffect(() => {
  $select.value?.attr('readonly', get(props.element.isReadOnly));
});

watchEffect(() => {
  $select.value?.toggleClass('form-required', get(props.element.isValid));
});

onMounted(() => {
  if (!selectElement.value) {
    return;
  }

  $select.value = jQuery(selectElement.value);

  get($select)
    ?.selectWoo({
      width: 'auto',
      data: get(options).map((option) => ({
        id: option.value as string | number,
        text: option.label,
        disabled: option.disabled,
      })),
    })
    .val(props.modelValue ?? get(props.element.ref))
    .trigger('change')
    .on('change', () => {
      emit('update:modelValue', get($select)?.val() ?? []);
    });
});

onBeforeUnmount(() => {
  get($select)?.off().selectWoo('destroy');
});
</script>
