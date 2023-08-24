import {type AddressFields} from '@myparcel-pdk/checkout';

// TODO: Get fields from the backend

const addressBase = {
  address1: `address_1`,
  city: `city`,
  country: `country`,
  eoriNumber: `eori_number`,
  number: `house_number`,
  numberSuffix: `house_number_suffix`,
  postalCode: `postcode`,
  street: `street_name`,
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
