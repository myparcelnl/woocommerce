type State = Record<string, unknown>;

type InitialData<T extends State> = {
  onUpdate?: (newState: T, oldState: T) => void;
  state: T;
};

type Store<T extends State> = {
  set: (newState: Partial<T>) => void;
  state: T;
};

const storedState: Record<string, State> = {};

export const createStore = <T extends State>(name: string, initialData: () => InitialData<T>): (() => Store<T>) => {
  if (!storedState[name]) {
    const data = initialData();

    storedState[name] = data;
    storedState[name].set = (newState: Partial<T>) => {
      const oldState = {...data.state};
      Object.assign(data.state, newState);

      // eslint-disable-next-line no-console
      console.log('%cSET', 'color: #0f0', name, {newState, oldState}, !!data.onUpdate);

      data.onUpdate?.({...data.state}, oldState);
    };
  }

  return () => {
    return storedState[name] as Store<T>;
  };
};
