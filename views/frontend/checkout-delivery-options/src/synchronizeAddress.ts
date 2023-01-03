import {getAddressField} from './utils/getAddressField';
import {hasSplitAddressFields} from './hasSplitAddressFields';
import {hasAddressType} from './hasAddressType';
import {fillCheckoutFields} from './fillCheckoutFields';
import {getAddressParts} from './getAddressParts';
import {updateAddress} from './updateAddress';

/**
 * Sync addresses between split and non-split address fields.
 *
 * @param {Event} event
 * @param {String} newCountry
 */
export const synchronizeAddress = (event, newCountry) => {
  if (!isUsingSplitAddressFields) {
    return;
  }

  const data = jQuery('form').serializeArray();

  ['shipping', 'billing'].forEach((addressType) => {
    if (!hasAddressType(addressType)) {
      return;
    }

    const typeCountry = data.find((item) => item.name === `${addressType}_country`);
    const hasAddressTypeCountry = previousCountry.hasOwnProperty(addressType);
    const countryChanged = previousCountry[addressType] !== newCountry;

    if (!hasAddressTypeCountry || countryChanged) {
      previousCountry[addressType] = typeCountry.value;
    }

    if (!countryChanged) {
      return;
    }

    if (hasSplitAddressFields(newCountry)) {
      const parts = getAddressParts();

      fillCheckoutFields(parts);
    } else {
      const [FIELD_HOUSE_NUMBER, FIELD_HOUSE_NUMBER_SUFFIX, FIELD_STREET_NAME] = [
        FIELD_HOUSE_NUMBER,
        FIELD_HOUSE_NUMBER_SUFFIX,
        FIELD_STREET_NAME,
      ].map((fieldName) => getAddressField(fieldName));

      const number = FIELD_HOUSE_NUMBER.value || '';
      const street = FIELD_STREET_NAME.value || '';
      const suffix = FIELD_HOUSE_NUMBER_SUFFIX.value || '';

      fillCheckoutFields({
        address_1: `${street} ${number}${suffix}`.trim(),
      });
    }

    updateAddress();
  });
};
