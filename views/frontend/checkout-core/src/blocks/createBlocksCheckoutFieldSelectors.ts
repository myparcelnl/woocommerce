import {type AddressFields} from '@myparcel-pdk/checkout';
import {createFields, createId} from '../utils';
import {ADDRESS_FIELDS_BLOCKS_CHECKOUT} from './constants';

/**
 * Field names that contain a slash are custom fields, like those of our plugin. Example: myparcelnl/street.
 * The ID of this field will be <addressType>-myparcelnl-street.
 */
export const createBlocksCheckoutFieldSelectors = (prefix: string): AddressFields => {
  return createFields(ADDRESS_FIELDS_BLOCKS_CHECKOUT, (val) => {
    return createId(prefix + val.replace('/', '-'));
  });
};
