import {getFieldValue, useSettingsStore} from '@myparcel-woocommerce/frontend-common';

export const hasTaxFields = (): boolean => {
  const settings = useSettingsStore();

  let hasTaxFields = true;

  if (settings.state.hasDeliveryOptions) {
    // eslint-disable-next-line @typescript-eslint/prefer-nullish-coalescing
    const {carrier} = JSON.parse(getFieldValue(settings.state.hiddenInputName) || '{}');

    hasTaxFields = settings.state.carriersWithTaxFields.includes(carrier);
  }

  return hasTaxFields;
};
