import {FIELD_ADDRESS, FIELD_HOUSE_NUMBER, FIELD_HOUSE_NUMBER_SUFFIX, FIELD_STREET_NAME} from './data';
import {getAddressField} from './utils';

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
  const splitStreetRegex = /(.*?)\s?(\d{1,4})[/\s-]{0,2}([A-z]\d{1,3}|-\d{1,4}|\d{2}\w{1,2}|[A-z][A-z\s]{0,3})?$/;
  const address = addressField?.value;
  const result = splitStreetRegex.exec(address ?? '');

  const parts: AddressParts = {
    street_name: '',
    house_number: '',
    house_number_suffix: '',
  };

  parts[FIELD_STREET_NAME] = result ? result[1] : null;
  parts[FIELD_HOUSE_NUMBER] = result ? result[2] : null;
  parts[FIELD_HOUSE_NUMBER_SUFFIX] = result ? result[3] : null;

  return parts;
};
