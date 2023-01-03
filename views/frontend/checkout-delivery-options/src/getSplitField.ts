import {hasSplitAddressFields} from './hasSplitAddressFields';
import {FIELD_ADDRESS, FIELD_HOUSE_NUMBER} from './data/fields';

/**
 * If split fields are used add house number to the fields. Otherwise use address line 1.
 *
 * @returns {String}
 */
export const getSplitField = () => (hasSplitAddressFields() ? FIELD_HOUSE_NUMBER : FIELD_ADDRESS);
