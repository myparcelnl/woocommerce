export const validateMyParcelConfig = (): void => {
  if (!window.hasOwnProperty('MyParcelConfig')) {
    throw 'window.MyParcelConfig not found!';
  }

  if (typeof window.MyParcelConfig === 'string') {
    window.MyParcelConfig = JSON.parse(window.MyParcelConfig);
  }
};
