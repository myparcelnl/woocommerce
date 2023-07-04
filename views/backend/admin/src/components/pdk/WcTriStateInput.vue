<template>
  <div
    v-test="AdminComponent.TriStateInput"
    :class="config?.cssUtilities?.displayFlex">
    <input
      v-model="model"
      type="hidden"
      :name="id" />

    <PdkToggleInput
      v-model="toggleModel"
      :class="config?.cssUtilities?.marginYAuto"
      :element="toggleElement" />

    <PdkButton
      :size="Size.ExtraSmall"
      class="!mypa-float-none !mypa-ml-1"
      :class="config?.cssUtilities?.displayFlex"
      :title="inheritElement?.label"
      @click="inheritModel = !inheritModel">
      <span
        class="dashicons"
        :class="[
          config?.cssUtilities?.marginYAuto,
          {
            'dashicons-lock': inheritModel,
            'dashicons-unlock mypa-text-green-600': !inheritModel,
          },
        ]"
        role="none" />

      <PdkCheckboxInput
        v-model="inheritModel"
        tabindex="-1"
        :element="{...inheritElement, label: undefined}"
        class="mypa-sr-only" />
    </PdkButton>
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
  generateFieldId,
  Size,
} from '@myparcel-pdk/admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<PdkElementProps<T>>();
const emit = defineEmits<PdkElementEmits<T>>();

const config = useAdminConfig();

const id = generateFieldId(props.element);

const {inheritElement, toggleElement, inheritModel, toggleModel, model} = useTriStateInputContext(props, emit);
</script>
