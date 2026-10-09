import {build} from 'vite';
import {beforeAll, describe, expect, it} from 'vitest';
import {createViteConfig} from './index.js';

const fixtures = new URL('./__fixtures__/', import.meta.url).pathname;

const buildIife = async (fixture, mode = 'production') => {
  const config = await createViteConfig({
    root: fixtures,
    configFile: false,
    logLevel: 'silent',
    build: {
      write: false,
      minify: false,
      lib: {
        entry: `${fixtures}${fixture}`,
        name: 'Fixture',
        formats: ['iife'],
      },
    },
  })({command: 'build', mode});

  const result = await build(config);
  const {output} = Array.isArray(result) ? result[0] : result;

  return output[0].code;
};

const runIife = (code, Vue = {}) => new Function('Vue', `${code}\nreturn Fixture;`)(Vue);

describe('createViteConfig', () => {
  let vueDemiCode;

  beforeAll(async () => {
    vueDemiCode = await buildIife('vue-demi.js');
  });

  it('builds an iife that imports vue-demi without calling require', () => {
    expect(vueDemiCode).not.toContain('require(');
  });

  it('builds an iife that gets vue-demi from the global Vue', () => {
    const fixture = runIife(vueDemiCode, {ref: (value) => ({value})});

    expect(fixture.value).toEqual({value: 1});
    expect(fixture.isVue3).toBe(true);
  });

  it.each([
    ['production', 'production'],
    ['development', 'development'],
  ])('replaces process.env.NODE_ENV in a %s build', async (mode, expected) => {
    const code = await buildIife('node-env.js', mode);

    expect(code).not.toContain('process');
    expect(runIife(code).nodeEnv).toBe(expected);
  });
});
