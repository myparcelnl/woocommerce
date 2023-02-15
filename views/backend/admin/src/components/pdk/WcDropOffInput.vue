<template>
  <div>
    <ul>
      <template
        v-for="[day, human] in Object.entries(weekdaysObject)"
        :key="day">
        <li>
          <span>{{ human }}</span>

          <PdkToggleInput
            v-model="toggleRefs[day]"
            :element="toggleElements[day]" />

          <div v-if="toggleRefs[day]">
            <PdkTimeInput
              v-model="cutoffRefs[day]"
              :element="cutoffElements[day]" />
          </div>
        </li>
      </template>
    </ul>
  </div>
</template>

<script lang="ts" setup>
import {ElementInstance, Settings, useDropOffInputContext} from '@myparcel-pdk/admin/src';
import {PropType} from 'vue';

const props = defineProps({
  // eslint-disable-next-line vue/no-unused-properties
  element: {
    type: Object as PropType<ElementInstance>,
    required: true,
  },

  modelValue: {
    type: Object as PropType<Settings.ModelDropOffPossibilities>,
    required: true,
  },
});

const emit = defineEmits(['update:modelValue']);

const {weekdaysObject, cutoffElements, toggleElements, toggleRefs, cutoffRefs} = useDropOffInputContext(
  props.modelValue,
  emit,
);
</script>
