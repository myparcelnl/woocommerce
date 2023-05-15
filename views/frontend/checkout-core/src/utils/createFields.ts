import {AddressFields} from '@myparcel-pdk/checkout/src';

// TODO: Get fields from the backend

const addressBase = {
  address1: `address_1`,
  city: `city`,
  country: `country`,
  eoriNumber: `eori_number`,
  number: `number`,
  numberSuffix: `number_suffix`,
  postalCode: `postcode`,
  street: `street`,
  vatNumber: `vat_number`,
};

export const createFields = (
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
