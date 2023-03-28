import {
  AddressType,
  FIELD_ADDRESS_1,
  FIELD_NUMBER,
  FIELD_NUMBER_SUFFIX,
  FIELD_STREET,
  getFieldValue,
  hasAddressType,
  hasSplitAddressFields,
  setFieldValue,
  useSettingsStore,
} from '@myparcel-woocommerce/frontend-common/src';
import {fillAddressFields, getAddressParts} from '../address';

/**
 * Sync addresses between split and non-split address fields.
 */
export const synchronizeAddress = (event: Event, newCountry: string): void => {
  const settings = useSettingsStore();

  if (settings.state.hasSplitAddressFields) {
    return;
  }

  [AddressType.SHIPPING, AddressType.BILLING].forEach((addressType) => {
    if (!hasAddressType(addressType)) {
      return;
    }

    if (hasSplitAddressFields(newCountry)) {
      const parts = getAddressParts();

      fillAddressFields(parts);
    } else {
      const [number, street, suffix] = [FIELD_NUMBER, FIELD_NUMBER_SUFFIX, FIELD_STREET].map(
        (fieldName) => getFieldValue(fieldName) ?? '',
      );

      setFieldValue(FIELD_ADDRESS_1, `${street} ${number} ${suffix}`.trim(), addressType);
    }
  });
};
