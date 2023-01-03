import {AddressType} from './types';

type Store = {
  shippingMethod: string | null;
  addressType: AddressType | null;
  hasDeliveryOptions: boolean;
};

let store: Store;

export function initializeStore(): void {
  store = {
    addressType: null,
    hasDeliveryOptions: false,
    shippingMethod: null,
  };
}

export function getStore(): Store {
  return store;
}

export function getStoreValue<K extends keyof Store>(key: K): Store[K] {
  return store[key];
}

export function setStoreValue<K extends keyof Store>(key: K, value: Store[K]): void {
  store[key] = value;
}
