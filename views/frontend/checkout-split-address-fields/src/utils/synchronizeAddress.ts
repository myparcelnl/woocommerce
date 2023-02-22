import {
  AddressType,
  FIELD_HOUSE_NUMBER,
  FIELD_HOUSE_NUMBER_SUFFIX,
  FIELD_STREET_NAME,
  fillSplitAddressFields,
  getAddressParts,
  getFieldValue,
  hasAddressType,
  hasSplitAddressFields,
  useSettingsStore,
} from '@myparcel-woocommerce/frontend-common/src';

/**
 * Sync addresses between split and non-split address fields.
 */
export const synchronizeAddress = (event: Event, newCountry: string): void => {
  const settings = useSettingsStore();

  if (settings.state.hasSplitAddressFields) {
    return;
  }

  const data = jQuery('form').serializeArray();

  [AddressType.SHIPPING, AddressType.BILLING].forEach((addressType) => {
    if (!hasAddressType(addressType)) {
      return;
    }

    // const typeCountry = data.find((item) => item.name === `${addressType}_country`);
    // const hasAddressTypeCountry = previousCountry.hasOwnProperty(addressType);
    // const countryChanged = previousCountry[addressType] !== newCountry;
    //
    // if (!hasAddressTypeCountry || countryChanged) {
    //   previousCountry[addressType] = typeCountry?.value;
    // }

    // if (!countryChanged) {
    //   return;
    // }

    if (hasSplitAddressFields(newCountry)) {
      const parts = getAddressParts();

      fillSplitAddressFields(parts);
    } else {
      const [number, street, suffix] = [FIELD_HOUSE_NUMBER, FIELD_HOUSE_NUMBER_SUFFIX, FIELD_STREET_NAME].map(
        (fieldName) => getFieldValue(fieldName) ?? '',
      );

      fillSplitAddressFields({
        street: `${street} ${number}${suffix}`.trim(),
      });
    }
  });
};
