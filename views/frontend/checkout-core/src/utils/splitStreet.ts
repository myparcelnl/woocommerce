/* eslint-disable @typescript-eslint/no-magic-numbers */

export interface StreetParts {
  number: string;
  numberSuffix: string;
  street: string;
}

/**
 * Very simple attempt at quickly splitting a street into street, number and number suffix. Not interested in the 999999 other edge cases.
 */
export const splitStreet = (currentFullStreet: string): StreetParts => {
  const reverseStreet = (currentFullStreet?.split(/ +/) ?? []).reverse().join(' ');
  const result = /(\d+)(?:[-/,]+\s*|(?: b|box|bus|bte|boite|boîte))?([A-z0-9]+)?/i.exec(reverseStreet);

  const number = String(result?.[1] ?? '');
  const numberSuffix = result?.[2] ?? reverseStreet.substring(0, result?.index).trim();

  const street = reverseStreet
    .substring((result?.index ?? 0) + (result?.[0]?.length ?? 0))
    .trim()
    .split(' ')
    .reverse()
    .join(' ');

  if (number.length && street.length) {
    return {street, number, numberSuffix};
  }

  // Fall back to just returning the full street as street
  return {street: currentFullStreet, number: '', numberSuffix: ''};
};
