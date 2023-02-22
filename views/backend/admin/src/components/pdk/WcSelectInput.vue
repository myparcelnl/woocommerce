<template>
  <select
    :id="id"
    ref="selectElement"
    v-model="model"
    v-test="'SelectInput'"
    :class="{
      disabled: options.length === 1 || element.isDisabled || element.isSuspended,
    }"
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
import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
import {generateFieldId, useElement} from '@myparcel-pdk/admin/src';
import {SelectOption} from '@myparcel-pdk/common/src';
import {useVModel} from '@vueuse/core';

const props = defineProps({
  // eslint-disable-next-line vue/no-unused-properties
  modelValue: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const element = useElement();

const model = useVModel(props, undefined, emit);

const options = computed<SelectOption[]>(() => {
  return element.props?.options ?? [];
});

const id = generateFieldId();

const selectElement = ref<HTMLElement | null>(null);

const $select = ref<JQuery | null>(null);

onMounted(() => {
  if (!selectElement.value) {
    return;
  }

  $select.value = jQuery(selectElement.value);

  $select.value.selectWoo({width: 'auto'}).on('change', (event) => {
    model.value = event.currentTarget?.value;
  });

  watch(
    options,
    (value) => {
      if ((model.value && options.value.some((option) => option.value === model.value)) || value.length === 0) {
        return;
      }

      model.value = value[0].value;
    },
    {immediate: options.value.length > 0},
  );
});

onBeforeUnmount(() => {
  $select.value?.selectWoo('destroy');
});
</script>
