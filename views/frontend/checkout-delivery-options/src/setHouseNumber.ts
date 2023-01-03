import {FIELD_ADDRESS, FIELD_HOUSE_NUMBER} from './data';
import {getAddressField} from './utils';
import {getHouseNumber} from './getHouseNumber';
import {hasSplitAddressFields} from './hasSplitAddressFields';

/**
 * Set the house number.
 */
export const setHouseNumber = (number: number | string): void => {
  const addressField = getAddressField(FIELD_ADDRESS);
  const address = addressField?.value;
  const oldHouseNumber = getHouseNumber();

  const houseNumber = number.toString();

  if (hasSplitAddressFields() && addressField) {
    if (oldHouseNumber) {
      addressField.value = address?.replace(oldHouseNumber, houseNumber) ?? '';
    } else {
      addressField.value = (address ?? '') + houseNumber;
    }
  } else {
    const houseNumberField = getAddressField(FIELD_HOUSE_NUMBER);

    if (houseNumberField) {
      houseNumberField.value = houseNumber;
    }
  }
};
