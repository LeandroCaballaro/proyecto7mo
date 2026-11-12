import { beforeEach, describe, expect, it, vi } from 'vitest';

// Base en memoria que imita las dos tablas de caché.
const store = new Map<string, { value: string; expiresAt: Date }>();
const translations = new Map<string, string>();

vi.mock('../src/lib/prisma', () => ({
  prisma: {
    externalCache: {
      findUnique: async ({ where }: { where: { key: string } }) =>
        store.has(where.key) ? { key: where.key, ...store.get(where.key)! } : null,
      upsert: async ({
        where,
        create,
      }: {
        where: { key: string };
        create: { value: string; expiresAt: Date };
      }) => {
        store.set(where.key, { value: create.value, expiresAt: create.expiresAt });
      },
    },
    translationCache: {
      findUnique: async ({ where }: { where: { hash: string } }) =>
        translations.has(where.hash) ? { translated: translations.get(where.hash)! } : null,
      upsert: async ({
        where,
        create,
      }: {
        where: { hash: string };
        create: { translated: string };
      }) => {
        translations.set(where.hash, create.translated);
      },
    },
  },
}));

const { cached } = await import('../src/lib/cache');
const { translateText } = await import('../src/lib/translator');

beforeEach(() => {
  store.clear();
  translations.clear();
  vi.restoreAllMocks();
});

describe('Caché de servicios externos', () => {
  it('pide una sola vez mientras la entrada está vigente', async () => {
    const load = vi.fn(async () => ({ metas: [1, 2, 3] }));
    expect(await cached('k', 60_000, load)).toEqual({ metas: [1, 2, 3] });
    expect(await cached('k', 60_000, load)).toEqual({ metas: [1, 2, 3] });
    expect(load).toHaveBeenCalledTimes(1);
  });

  it('vuelve a pedir cuando la entrada venció', async () => {
    const load = vi.fn(async () => 'dato');
    await cached('k', -1, load);
    await cached('k', 60_000, load);
    expect(load).toHaveBeenCalledTimes(2);
  });

  it('no guarda nada si la carga falla', async () => {
    await expect(
      cached('k', 60_000, async () => Promise.reject(new Error('caído'))),
    ).rejects.toThrow('caído');
    expect(store.size).toBe(0);
  });
});

describe('Caché de traducciones', () => {
  it('traduce una vez y después responde desde la caché', async () => {
    const fetchMock = vi
      .spyOn(globalThis, 'fetch')
      .mockResolvedValue(
        new Response(
          JSON.stringify({ responseStatus: 200, responseData: { translatedText: 'Hola mundo.' } }),
        ),
      );
    expect(await translateText('Hello world.')).toBe('Hola mundo.');
    expect(await translateText('Hello world.')).toBe('Hola mundo.');
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });
});
