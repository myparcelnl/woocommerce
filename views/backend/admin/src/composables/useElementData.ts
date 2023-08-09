import {computed, type MaybeRef, unref, type ComputedRef} from 'vue';
import {type ElementInstance} from '@myparcel-pdk/admin';
import {type AnyElementInstance} from '@myparcel/vue-form-builder';

export const useElementData = (
  element: MaybeRef<AnyElementInstance | ElementInstance>,
): {
  isInteractive: ComputedRef<boolean>;
} => {
  const resolvedElement = unref(element);

  return {
    isInteractive: computed(() => resolvedElement.hasOwnProperty('ref')),
  };
};
