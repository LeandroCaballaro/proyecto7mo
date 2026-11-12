import { logger } from './logger';
import { prisma } from './prisma';

/**
 * Caché persistente en la base (tabla external_cache) para respuestas de servicios externos.
 * Si la base falla, se sigue sin caché: nunca rompe la operación principal.
 */

export const HOUR = 60 * 60 * 1000;
export const DAY = 24 * HOUR;

export async function cached<T>(key: string, ttlMs: number, load: () => Promise<T>): Promise<T> {
  const safeKey = key.slice(0, 191);

  try {
    const hit = await prisma.externalCache.findUnique({ where: { key: safeKey } });
    if (hit && hit.expiresAt > new Date()) return JSON.parse(hit.value) as T;
  } catch (err) {
    logger.warn({ err }, 'No se pudo leer la caché externa');
  }

  const value = await load();

  try {
    const data = { value: JSON.stringify(value), expiresAt: new Date(Date.now() + ttlMs) };
    await prisma.externalCache.upsert({
      where: { key: safeKey },
      create: { key: safeKey, ...data },
      update: data,
    });
  } catch (err) {
    logger.warn({ err }, 'No se pudo guardar en la caché externa');
  }
  return value;
}

/** Borra entradas vencidas. Se llama periódicamente desde el servidor. */
export function pruneExpiredCache() {
  return prisma.externalCache.deleteMany({ where: { expiresAt: { lt: new Date() } } });
}
