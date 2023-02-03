import {FIELD_ADDRESS, FIELD_HOUSE_NUMBER} from './data';
import {getAddressField} from './utils';
import {getHouseNumber} from './getHouseNumber';
import {hasSplitAddressFields} from './hasSplitAddressFields';
import {setFieldValue} from './dom/setFieldValue';

/**
 * Set the house number.
 */
export const setHouseNumber = (number: number | string): void => {
  const addressField = getAddressField(FIELD_ADDRESS);
  const houseNumber = number.toString();

  if (!hasSplitAddressFields() && addressField) {
    const oldHouseNumber = getHouseNumber();
    const address = addressField?.value;

    if (oldHouseNumber) {
      addressField.value = address?.replace(oldHouseNumber, houseNumber) ?? '';
    } else {
      addressField.value = (address ?? '') + houseNumber;
    }
  } else {
    setFieldValue(FIELD_HOUSE_NUMBER, houseNumber);
  }
};
