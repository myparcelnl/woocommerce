import {getAddressField} from './utils/getAddressField';
import {FIELD_ADDRESS, FIELD_HOUSE_NUMBER, FIELD_HOUSE_NUMBER_SUFFIX, FIELD_STREET_NAME} from './data/fields';

export interface AddressParts {
  house_number: string | null;
  house_number_suffix: string | null;
  street_name: string | null;
}

/**
 *
 * @returns {{house_number_suffix: (String | null), house_number: (String | null), street_name: (String | null)}}
 */
export const getAddressParts = (): AddressParts => {
  const addressField: HTMLInputElement | null = getAddressField(FIELD_ADDRESS);

  const address = addressField?.value;
  const result = splitStreetRegex.exec(address);

  const parts = {};

  parts[FIELD_STREET_NAME] = result ? result[1] : null;
  parts[FIELD_HOUSE_NUMBER] = result ? result[2] : null;
  parts[FIELD_HOUSE_NUMBER_SUFFIX] = result ? result[3] : null;

  return parts;
};
