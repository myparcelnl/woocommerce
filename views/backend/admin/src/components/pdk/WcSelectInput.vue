<template>
  <select
    :id="element.name"
    ref="selectElement"
    v-model="model"
    class="select"
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
import {PropType, UnwrapNestedRefs, computed, defineComponent, onMounted, ref, watch} from 'vue';
import {InteractiveElementInstance} from '@myparcel-vfb/core';
import {SelectOption} from '@myparcel-pdk/common';
import {useVModel} from '@vueuse/core';

export default defineComponent({
  name: 'WcSelectInput',

  props: {
    element: {
      type: Object as PropType<UnwrapNestedRefs<InteractiveElementInstance>>,
      required: true,
    },

    // eslint-disable-next-line vue/no-unused-properties
    modelValue: {
      type: [String, Number],
      default: null,
    },
  },

  setup: (props, ctx) => {
    const selectElement = ref<HTMLElement | null>(null);

    const model = useVModel(props, 'modelValue', ctx.emit);
    const options = computed<SelectOption[]>(() => {
      return props.element.props?.options ?? [];
    });

    watch(options, () => {
      if (options.value.length === 1 || !model.value) {
        model.value = options.value[0].value;
      }
    });

    // todo: fix select2 styling
    // onMounted(() => {
    //   if (selectElement.value) {
    //     const $select = jQuery(selectElement.value);
    //
    //     $select
    //       .selectWoo({
    //         containerCss: {
    //           'min-width': '0 !important',
    //           width: '100%',
    //         },
    //
    //         dropdownCss: {
    //           'min-width': '0 !important',
    //           width: '100%',
    //         },
    //       })
    //       .on('change', (event) => {
    //         console.log(event.target.value);
    //         model.value = event.target.value;
    //       });
    //   }
    // });

    return {
      model,
      options,
      selectElement,
    };
  },
});
</script>
