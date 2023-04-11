import {AddressType} from '../';

export const usesAddressType = (addressType: AddressType): boolean => {
  const wrapper = document.querySelector(`.${addressType}_address`);

  return wrapper !== null && wrapper.getClientRects().length !== 0;
};
