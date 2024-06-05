<template>
  <!-- Not using PdkCol and PdkRow on purpose, the form groups need to be styled separately from other tables. -->

  <tr v-if="!isInteractive">
    <!-- This row avoids issues with table-layout: fixed if the first element on the page has colspan="2" -->
    <th class="!mypa-p-0" />
    <td class="!mypa-p-0" />
  </tr>

  <tr
    v-show="element.isVisible"
    v-test="AdminComponent.FormGroup"
    valign="top">
    <template v-if="isInteractive">
      <th
        class="titledesc"
        scope="row">
        <label :for="id">
          <slot name="label">
            {{ element.label }}
          </slot>

          <WcHelpTip :element="element" />
        </label>
      </th>

      <td>
        <div>
          <slot />
        </div>

        <WcDescription :element="element" />
      </td>
    </template>

    <td
      v-else
      class="!mypa-p-0 mypa-border-b mypa-border-gray-500"
      colspan="2">
      <slot />
    </td>
  </tr>
</template>

<script lang="ts" setup>
import {toRefs} from 'vue';
import {AdminComponent, generateFieldId, type ElementInstance} from '@myparcel-pdk/admin';
import WcHelpTip from '../WcHelpTip.vue';
import WcDescription from '../WcDescription.vue';
import {useElementData} from '../../composables';

// eslint-disable-next-line vue/no-unused-properties
const props = defineProps<{modelValue: boolean; element: ElementInstance}>();
const propRefs = toRefs(props);

const id = generateFieldId(propRefs.element.value);

const {isInteractive} = useElementData(propRefs.element);
</script>
