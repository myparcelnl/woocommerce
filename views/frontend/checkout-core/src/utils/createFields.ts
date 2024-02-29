import {type AddressFields} from '@myparcel-pdk/checkout';

export const createFields = (
  addressBase: Record<string, string>,
  prefix: string,
  callback: (string: string) => string = (string: string) => string,
): AddressFields =>
  Object.entries(addressBase).reduce(
    (acc, [key, value]) => ({
      ...acc,
      [key]: callback(`${prefix}${value}`),
    }),
    {} as AddressFields,
  );
