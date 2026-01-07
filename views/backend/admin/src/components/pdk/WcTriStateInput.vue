<template>
  <div
    v-test="[AdminComponent.TriStateInput, element]"
    :class="config?.cssUtilities?.displayFlex">
    <input
      v-model="model"
      :name="id"
      type="hidden" />

    <PdkToggleInput
      v-model="toggleModel"
      :class="config?.cssUtilities?.marginYAuto"
      :element="toggleElement" />

    <PdkButton
      :class="config?.cssUtilities?.displayFlex"
      :size="Size.ExtraSmall"
      :title="inheritElement?.label"
      class="!mypa-float-none !mypa-ml-1"
      @click="inheritModel = !inheritModel">
      <span
        :class="[
          config?.cssUtilities?.marginYAuto,
          {
            'dashicons-lock': inheritModel,
            'dashicons-unlock mypa-text-green-600': !inheritModel,
          },
        ]"
        class="dashicons"
        role="none" />

      <PdkCheckboxInput
        v-model="inheritModel"
        :element="{...inheritElement, label: undefined}"
        class="mypa-sr-only"
        tabindex="-1" />
    </PdkButton>
  </div>
</template>

<script generic="T extends TriStateValue" lang="ts" setup>
import {
  type PdkElementEmits,
  type PdkElementProps,
  type TriStateValue,
  useTriStateInputContext,
  AdminComponent,
  useAdminConfig,
  generateFieldId,
  Size,
} from '@myparcel-dev/pdk-admin';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<PdkElementProps<T>>();
const emit = defineEmits<PdkElementEmits<T>>();

const config = useAdminConfig();

const id = generateFieldId(props.element);

const {inheritElement, toggleElement, inheritModel, toggleModel, model} = useTriStateInputContext(props, emit);
</script>
