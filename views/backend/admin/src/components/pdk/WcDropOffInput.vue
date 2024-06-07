<template>
  <PdkTable v-test="[AdminComponent.DropOffInput, element]">
    <template
      v-for="[day, human] in Object.entries(weekdaysObject)"
      :key="day">
      <PdkTableRow>
        <PdkTableCol component="th">
          <label :for="`${id}_toggle_${day}`">{{ human }}</label>
        </PdkTableCol>

        <PdkTableCol>
          <PdkToggleInput
            v-model="toggleRefs[day]"
            :element="{...toggleElements[day], props: {...toggleElements[day], id: `${id}_toggle_${day}`}}" />
        </PdkTableCol>
      </PdkTableRow>

      <PdkTableRow v-if="toggleRefs[day]">
        <PdkTableCol>
          <label :for="`${id}_time_${day}`">{{ translate('settings_carrier_cutoff_time') }}</label>
        </PdkTableCol>

        <PdkTableCol>
          <PdkTimeInput
            v-model="cutoffRefs[day]"
            :element="{...cutoffElements[day], props: {...cutoffElements[day], id: `${id}_time_${day}`}}" />
        </PdkTableCol>
      </PdkTableRow>
    </template>
  </PdkTable>
</template>

<script lang="ts" setup>
import {
  AdminComponent,
  type ElementInstance,
  generateFieldId,
  useDropOffInputContext,
  useLanguage,
  type DropOffInputModelValue,
} from '@myparcel-pdk/admin';

const props = defineProps<{
  element: ElementInstance;
  // eslint-disable-next-line vue/no-unused-properties
  modelValue: DropOffInputModelValue;
}>();
const emit = defineEmits<(e: 'update:modelValue', value: DropOffInputModelValue) => void>();

const {weekdaysObject, cutoffElements, toggleElements, toggleRefs, cutoffRefs} = useDropOffInputContext(props, emit);

const id = generateFieldId();

const {translate} = useLanguage();
</script>
