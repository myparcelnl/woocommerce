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
import {computed, onBeforeUnmount, onMounted, ref, watch, watchEffect} from 'vue';
import {generateFieldId, useElement} from '@myparcel-pdk/admin/src';
import {SelectOption} from '@myparcel-pdk/common/src';
import {isOfType} from '@myparcel/ts-utils';
import {get} from '@vueuse/core';

const props = defineProps({
  // eslint-disable-next-line vue/no-unused-properties
  modelValue: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const model = computed({
  get: () => props.modelValue,
  set: (value) => {
    $select.value?.val(value).trigger('change');
    emit('update:modelValue', value);
  },
});

const element = useElement();
const id = generateFieldId();

// @ts-expect-error props are not typed
const options = computed<SelectOption[]>(() => element.props?.options ?? []);

const selectElement = ref<HTMLElement | null>(null);
const $select = ref<JQuery | null>(null);

watchEffect(() => {
  $select.value?.toggleClass('form-required', get(element.isValid));
  $select.value?.attr('disabled', get(element.isDisabled) || get(element.isSuspended));
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

  watch(
    options,
    (newOptions) => {
      const hasExistingValue = model.value && newOptions.some((option) => option.value === model.value);

      if (hasExistingValue || newOptions.length === 0) {
        return;
      }

      model.value = newOptions[0].value;
    },
    {immediate: options.value.length > 0},
  );
});

onBeforeUnmount(() => {
  $select.value?.selectWoo('destroy');
});
</script>
