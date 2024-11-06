import {type AddressFields, SeparateAddressField, AddressField} from '@myparcel-pdk/checkout';
import {type WcAddressObject} from '../types';
import {resolveAddressKey} from './resolveAddressKey';

/**
 * Resolve the address object to the correct keys.
 */
export const resolveAddress = (address: WcAddressObject | AddressFields): WcAddressObject => {
  const newEntries = Object.entries(address).reduce((acc, [key, value]) => {
    const resolvedKey = resolveAddressKey(key);

    acc.push([resolvedKey, value]);

    return acc;
  }, [] as [string, string][]);

  const newAddress = Object.fromEntries(newEntries);

  const streetKey = resolveAddressKey(SeparateAddressField.Street);
  const numberKey = resolveAddressKey(SeparateAddressField.Number);

  // if street and number are set, overwrite address 1 with the full street
  if (newAddress[streetKey] && newAddress[numberKey]) {
    const address1Key = resolveAddressKey(AddressField.Address1);
    const numberSuffixKey = resolveAddressKey(SeparateAddressField.NumberSuffix);

    newAddress[address1Key] = [newAddress[streetKey], newAddress[numberKey], newAddress[numberSuffixKey]]
      .filter(Boolean)
      .join(' ')
      .trim();
  }

  return newAddress;
};
