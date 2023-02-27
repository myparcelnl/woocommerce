<template>
  <PdkTable>
    <template
      v-for="[day, human] in Object.entries(weekdaysObject)"
      :key="day">
      <PdkTableRow>
        <PdkTableCol component="th">
          <label :for="`${id}_toggle_${day}`">{{ human }}</label>
        </PdkTableCol>

        <PdkTableCol>
          <PdkToggleInput
            :id="`${id}_toggle_${day}`"
            v-model="toggleRefs[day]"
            :element="toggleElements[day]" />
        </PdkTableCol>
      </PdkTableRow>

      <PdkTableRow v-if="toggleRefs[day]">
        <PdkTableCol>
          <label :for="`${id}_time_${day}`">{{ translate('settings_cutoff_time') }}</label>
        </PdkTableCol>

        <PdkTableCol>
          <PdkTimeInput
            :id="`${id}_time_${day}`"
            v-model="cutoffRefs[day]"
            :element="cutoffElements[day]" />
        </PdkTableCol>
      </PdkTableRow>
    </template>
  </PdkTable>
</template>

<script lang="ts" setup>
import {ElementInstance, Settings, generateFieldId, useDropOffInputContext, useLanguage} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';

const props = defineProps({
  modelValue: {
    type: Object as PropType<Settings.ModelDropOffPossibilities>,
    default: null,
  },

  // eslint-disable-next-line vue/no-unused-properties
  element: {
    type: Object as PropType<ElementInstance>,
    required: true,
  },
});

const emit = defineEmits(['update:modelValue']);

const {weekdaysObject, cutoffElements, toggleElements, toggleRefs, cutoffRefs} = useDropOffInputContext(
  props.modelValue,
  emit,
);

const id = generateFieldId();

const {translate} = useLanguage();
</script>
