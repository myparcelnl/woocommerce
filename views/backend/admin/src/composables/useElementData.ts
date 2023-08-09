import {computed, type MaybeRef, unref, type ComputedRef} from 'vue';
import {type ElementInstance} from '@myparcel-pdk/admin';

export const useElementData = (
  element: MaybeRef<ElementInstance>,
): {
  isInteractive: ComputedRef<boolean>;
} => {
  const resolvedElement = unref(element);

  return {
    isInteractive: computed(() => resolvedElement.hasOwnProperty('ref')),
  };
};
