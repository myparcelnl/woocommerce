<template>
  <div
    v-if="isInteractive"
    v-test="AdminComponent.FormGroup">
    <p class="form-field form-row form-row-full">
      <label :for="id">
        <slot name="label">
          {{ element.label }}
        </slot>
      </label>

      <WcHelpTip :element="element" />

      <slot />

      <WcDescription :element="element" />
    </p>
  </div>

  <div
    v-else
    class="options">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import {toRefs} from 'vue';
import {AdminComponent, generateFieldId} from '@myparcel-pdk/admin';
import {type AnyElementInstance} from '@myparcel/vue-form-builder';
import WcHelpTip from '../WcHelpTip.vue';
import WcDescription from '../WcDescription.vue';
import {useElementData} from '../../composables';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: boolean; element: AnyElementInstance}>();
const propRefs = toRefs(props);

const id = generateFieldId(propRefs.element.value);

const {isInteractive} = useElementData(propRefs.element);
</script>
