export const objectDiffers = (a: unknown, b: unknown): boolean => JSON.stringify(a) !== JSON.stringify(b);
