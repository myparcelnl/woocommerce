<template>
  <div
    v-if="isInteractive"
    v-test="AdminComponent.FormGroup"
    class="options_group">
    <p class="form-field">
      <label :for="id">
        <slot name="label">
          {{ element.label }}
        </slot>
      </label>

      <slot />

      <WcHelpTip :element="element" />
      <WcDescription :element="element" />
    </p>
  </div>

  <div v-else>
    <slot />
  </div>
</template>

<script lang="ts" setup>
import {toRefs} from 'vue';
import {AdminComponent, type ElementInstance, generateFieldId} from '@myparcel-pdk/admin';
import WcHelpTip from '../WcHelpTip.vue';
import WcDescription from '../WcDescription.vue';
import {useElementData} from '../../composables';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: boolean; element: ElementInstance}>();
const propRefs = toRefs(props);

const id = generateFieldId(propRefs.element.value);

const {isInteractive} = useElementData(propRefs.element);
</script>
