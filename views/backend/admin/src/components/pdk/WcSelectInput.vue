<template>
  <select
    :id="id"
    ref="selectElement"
    v-model="model"
    v-test="'SelectInput'"
    class="select">
    <option
      v-for="(item, index) in options"
      :key="index"
      v-test="'SelectInput__option'"
      :value="item.value"
      v-text="item.label" />
  </select>
</template>

<script lang="ts" setup>
import {ElementInstance, generateFieldId, useSelectInputContext} from '@myparcel-pdk/admin/src';
import {computed, onBeforeUnmount, onMounted, ref, watchEffect} from 'vue';
import {InteractiveElementInstance} from '@myparcel/vue-form-builder/src';
import {get} from '@vueuse/core';
import {isOfType} from '@myparcel/ts-utils';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: string | number; element: InteractiveElementInstance}>();
const emit = defineEmits<(e: 'update:modelValue', value: string | number) => void>();

const id = generateFieldId(props.element as ElementInstance);

const model = computed({
  get: () => props.modelValue,
  set: (value) => {
    $select.value?.val(value).trigger('change');
    emit('update:modelValue', value);
  },
});

// @ts-expect-error props are not typed
const {options} = useSelectInputContext(model, props.element.props?.options ?? []);

const selectElement = ref<HTMLElement | null>(null);
const $select = ref<JQuery | null>(null);

watchEffect(() => {
  $select.value?.toggleClass('form-required', get(props.element.isValid));
  // @ts-expect-error typescript doesn't get it
  $select.value?.attr('disabled', get(props.element.isDisabled) || get(props.element.isSuspended));
});

onMounted(() => {
  if (!selectElement.value) {
    return;
  }

  $select.value = jQuery(selectElement.value);

  const selectWoo = $select.value.selectWoo({width: 'auto'});

  selectWoo.on('change', (event) => {
    if (!isOfType<HTMLSelectElement>(event.target, 'value')) {
      return;
    }

    emit('update:modelValue', event.target.value);
  });
});

onBeforeUnmount(() => {
  $select.value?.selectWoo('destroy');
});
</script>
