import {describe, it, expect} from 'vitest';
import {AddressType} from '@myparcel-pdk/checkout';
import {getBlocksCheckoutConfig} from './getBlocksCheckoutConfig';

/**
 * @vitest-environment happy-dom
 */

describe('getBlocksCheckoutConfig', () => {
  it('has correct fields and formData config', () => {
    const {fields, formData} = getBlocksCheckoutConfig();

    expect(fields[AddressType.Billing]).toMatchSnapshot();
    expect(fields[AddressType.Shipping]).toMatchSnapshot();

    expect(formData[AddressType.Billing]).toMatchSnapshot();
    expect(formData[AddressType.Shipping]).toMatchSnapshot();
  });
});
