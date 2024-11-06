import {createId} from '../utils';

export const createSelector = (val: string, prefix: string) => {
  const matches = /(\w+)\/(\w+)$/.exec(val);

  if (!matches) {
    return createId(prefix + val);
  }

  return createId(`${prefix}${matches[1]}-${matches[1]}-${matches[2]}`);
};
