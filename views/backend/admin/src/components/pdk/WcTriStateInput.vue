<template>
  <div
    v-test="AdminComponent.TriStateInput"
    :class="config?.cssUtilities?.displayFlex">
    <PdkToggleInput
      v-model="toggleModel"
      :class="config?.cssUtilities?.marginYAuto"
      :element="element" />

    <label
      :title="inheritValueElement.label"
      class="!mypa-float-none !mypa-m-0"
      :class="config?.cssUtilities?.displayFlex">
      <span
        class="dashicons"
        :class="[
          config?.cssUtilities?.marginYAuto,
          {
            'dashicons-lock': inheritValueModel,
            'dashicons-unlock': !inheritValueModel,
          },
        ]"
        role="none" />

      <PdkCheckboxInput
        v-model="inheritValueModel"
        :element="inheritValueElement"
        class="mypa-sr-only" />
    </label>
  </div>
</template>

<script setup lang="ts" generic="T extends TriStateValue">
import {
  type PdkElementEmits,
  type PdkElementProps,
  type TriStateValue,
  useTriStateInputContext,
  AdminComponent,
  useAdminConfig,
} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<PdkElementProps<T>>();
const emit = defineEmits<PdkElementEmits<T>>();

const config = useAdminConfig();

const {inheritValueElement, inheritValueModel, toggleModel} = useTriStateInputContext(props, emit);
</script>
