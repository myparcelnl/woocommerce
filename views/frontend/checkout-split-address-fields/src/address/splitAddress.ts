import {AddressParts, SPLIT_STREET_REGEX} from '@myparcel-woocommerce/frontend-common';

export const splitAddress = (address?: string): AddressParts => {
  const parts = address?.split(SPLIT_STREET_REGEX);

  const [, street, number, numberSuffix] = parts ?? [];

  return {
    street: street ?? '',
    number: number ?? '',
    numberSuffix: numberSuffix ?? '',
  };
};
