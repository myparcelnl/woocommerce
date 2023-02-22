import {StoreListener} from './types';

type State = Record<string, unknown>;

type ListenerObject<S extends State> = {
  [L in StoreListener]?: Listeners<S>[L][];
};

type InitialData<S extends State = State> = {
  state: S;
  listeners?: ListenerObject<S>;
};

type Store<S extends State = State> = {
  set: (newState: Partial<S>) => void;
  state: S;
  listeners: ListenerObject<S>;
  on: <L extends StoreListener>(listener: L, callback: Listeners<S>[L]) => void;
};

type StoreData<S extends State = State, N extends string = string> = Record<N, Store<S>>;

const storedState: StoreData = {};

type Listeners<T extends State> = {
  [StoreListener.UPDATE]: (newState: T, oldState: T) => void | T;
};

export const createStore = <S extends State = State, N extends string = string>(
  name: N,
  initialData: () => InitialData<S>,
): (() => StoreData<S, N>[N]) => {
  if (!storedState[name]) {
    const resolvedData: InitialData<S> = initialData();

    storedState[name] = {
      ...resolvedData,

      listeners: resolvedData.listeners ?? {},

      on: (listener, callback) => {
        storedState[name].listeners[listener] ??= [];
        storedState[name].listeners[listener]?.push(callback);
      },

      set: (newState) => {
        const oldState = {...storedState[name].state};
        const state = Object.assign(storedState[name].state, newState);

        // eslint-disable-next-line no-console
        console.log('%cSET', 'color: #0f0', name, {newState, oldState});

        const updates = storedState[name].listeners?.update?.map((listener) => listener({...state}, oldState));

        if (updates?.length) {
          const additionalUpdate = updates.reduce((state, update) => {
            return {...state, ...update};
          }, {});

          Object.assign(storedState[name].state, additionalUpdate);
        }
      },
    };
  }

  return () => {
    return storedState[name];
  };
};
