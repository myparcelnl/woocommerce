import {type AnyPdkAddressField, type WcAddressField} from '../types';
import {ADDRESS_FIELDS_BLOCKS_CHECKOUT} from './constants';

export const resolveAddressKey = (key: AnyPdkAddressField | WcAddressField): WcAddressField => {
  const resolvedKeys: WcAddressField[] = Object.values(ADDRESS_FIELDS_BLOCKS_CHECKOUT);

  if (resolvedKeys.includes(key)) {
    return key;
  }

  // eslint-disable-next-line @typescript-eslint/no-unnecessary-type-assertion
  return ADDRESS_FIELDS_BLOCKS_CHECKOUT[key as AnyPdkAddressField] ?? key;
};
