import {createId} from '../utils';

export const createBlocksCheckoutSelector = (val: string, prefix: string): string => {
  const matches = /(\w+)\/(\w+)$/.exec(val);

  if (!matches) {
    return createId(prefix + val);
  }

  return createId(`${prefix}${matches[1]}-${matches[1]}-${matches[2]}`);
};
