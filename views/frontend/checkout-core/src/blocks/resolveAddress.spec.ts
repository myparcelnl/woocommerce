import {describe, it, expect} from 'vitest';
import {AddressField} from '@myparcel-pdk/checkout';
import {resolveAddress} from './resolveAddress';

/**
 * @vitest-environment happy-dom
 */

describe('resolveAddress', () => {
  it('resolves pdk address to wc address', () => {
    const address = {
      street: 'Antareslaan',
      number: '31',
      numberSuffix: 'abc',
      [AddressField.PostalCode]: '2132JE',
      [AddressField.City]: 'Hoofddorp',
      [AddressField.Country]: 'NL',
    };

    const resolvedAddress = resolveAddress(address);

    expect(resolvedAddress).toEqual({
      address_1: 'Antareslaan 31 abc',
      'myparcelnl/street_name': 'Antareslaan',
      'myparcelnl/house_number': '31',
      'myparcelnl/house_number_suffix': 'abc',
      postcode: '2132JE',
      city: 'Hoofddorp',
      country: 'NL',
    });
  });

  it('leaves wc address as wc address', () => {
    const address = {
      address_1: 'Antareslaan 31',
      address_2: 'some info',
      'myparcelnl/street_name': 'Antareslaan',
      'myparcelnl/house_number': '31',
      'myparcelnl/house_number_suffix': 'abc',
      postcode: '2132JE',
      city: 'Hoofddorp',
      country: 'NL',
    };

    const resolvedAddress = resolveAddress(address);

    expect(resolvedAddress).toEqual({
      ...address,
      address_1: 'Antareslaan 31 abc',
    });
  });
});
