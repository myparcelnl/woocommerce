<template>
  <PdkTableRow v-if="!isInteractive">
    <!-- This row avoids issues with table-layout: fixed if the first element on the page has colspan="2" -->
    <PdkTableCol
      class="!mypa-p-0"
      component="th" />
    <PdkTableCol class="!mypa-p-0" />
  </PdkTableRow>

  <PdkTableRow
    v-show="element.isVisible"
    v-test="AdminComponent.FormGroup"
    valign="top">
    <template v-if="isInteractive">
      <PdkTableCol
        class="titledesc"
        component="th"
        scope="row">
        <label :for="id">
          <slot name="label">
            {{ element.label }}
          </slot>

          <WcHelpTip :element="element" />
        </label>
      </PdkTableCol>

      <PdkTableCol>
        <div class="mypa-max-w-md">
          <slot />
        </div>

        <WcDescription :element="element" />
      </PdkTableCol>
    </template>

    <PdkTableCol
      v-else
      class="!mypa-p-0 mypa-border-b mypa-border-gray-500"
      colspan="2">
      <slot />
    </PdkTableCol>
  </PdkTableRow>
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
