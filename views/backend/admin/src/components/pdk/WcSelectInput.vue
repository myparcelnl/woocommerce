<template>
  <select
    :id="id"
    ref="selectElement"
    v-model="model"
    class="select mypa-w-full mypa-mw-48"
    :class="{
      disabled: options.length === 1 || element.isDisabled || element.isSuspended,
    }">
    <option
      v-for="(item, index) in options"
      :key="index"
      :value="item.value"
      v-text="item.label" />
  </select>
</template>

<script lang="ts">
import {ElementInstance, generateFieldId} from '@myparcel/pdk-frontend';
import {PropType, computed, defineComponent, onBeforeUnmount, onMounted, ref} from 'vue';
import {SelectOption} from '@myparcel-pdk/common';

const CSS = {
  'min-width': '0 !important',
  width: '100%',
};

export default defineComponent({
  name: 'WcSelectInput',

  props: {
    element: {
      type: Object as PropType<ElementInstance>,
      required: true,
    },

    // eslint-disable-next-line vue/no-unused-properties
    modelValue: {
      type: [String, Number],
      default: null,
    },
  },

  emits: ['update:modelValue'],

  setup: (props, ctx) => {
    const model = computed({
      get: () => {
        return props.modelValue;
      },
      set: (value) => {
        ctx.emit('update:modelValue', value);
        $select.value?.val(value);
        $select.value?.trigger('change.select2', {data: {internal: true}});
      },
    });

    const selectElement = ref<HTMLElement | null>(null);

    const $select = ref<JQuery | null>(null);

    const options = computed<SelectOption[]>(() => {
      return props.element.props?.options ?? [];
    });

    onMounted(() => {
      if (!selectElement.value) {
        return;
      }

      $select.value = jQuery(selectElement.value);

      $select.value.selectWoo({containerCss: CSS, dropdownCss: CSS}).on('change', (event) => {
        model.value = event.currentTarget?.value;
      });

      if (options.value.length === 1 || (!model.value && options.value.length > 0)) {
        model.value = options.value[0].value;
      }
    });

    onBeforeUnmount(() => {
      $select.value?.selectWoo('destroy');
    });

    return {
      id: generateFieldId(props.element),
      model,
      options,
      selectElement,
    };
  },
});
</script>
