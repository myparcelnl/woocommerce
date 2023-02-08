import {StoreListener} from './types';

type State = Record<string, unknown>;

type ListenerObject = {
  [L in StoreListener]?: Listeners[L][];
};

type InitialData<T extends State> = {
  state: T;
  listeners?: ListenerObject;
};

type Store<T extends State> = {
  set: (newState: Partial<T>) => void;
  state: T;
  listeners: ListenerObject;
  on: <L extends StoreListener>(listener: L, callback: Listeners[L]) => void;
};

type StoreData<T extends State, N extends string> = Record<N, Store<T>>;

const storedState: StoreData<State, string> = {};

type Listeners = {
  [StoreListener.UPDATE]: (newState: State, oldState: State) => void;
};

export const createStore = <T extends State, N extends string = string>(
  name: N,
  initialData: () => InitialData<T>,
): (() => StoreData<T, N>[N]) => {
  if (!storedState[name]) {
    storedState[name] = {
      listeners: {},
      ...initialData(),

      on: (listener, callback) => {
        storedState[name].listeners[listener] ??= [];
        storedState[name].listeners[listener]?.push(callback);
      },

      set: (newState) => {
        const oldState = {...storedState[name].state};
        const state = Object.assign(storedState[name].state, newState);

        // eslint-disable-next-line no-console
        console.log('%cSET', 'color: #0f0', name, {newState, oldState});

        storedState[name].listeners?.update?.map((listener) => listener({...state}, oldState));
      },
    };
  }

  return () => {
    return storedState[name] as Store<T>;
  };
};
