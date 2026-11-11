import { env } from '../config/env';
import { logger } from './logger';

/**
 * Traducción inglés -> español con MyMemory (gratis, sin registro).
 * Cupo: 5.000 caracteres/día por IP, o 50.000 si se envía TRANSLATE_EMAIL.
 * Si se agota el cupo, deja de intentar hasta reiniciar el proceso y devuelve null.
 */

const ENDPOINT = 'https://api.mymemory.translated.net/get';
/** MyMemory rechaza consultas de más de 500 bytes. */
const MAX_CHUNK_BYTES = 450;

let quotaExhausted = false;

function splitIntoChunks(text: string): string[] {
  const sentences = text.match(/[^.!?]+[.!?]*\s*/g) ?? [text];
  const chunks: string[] = [];
  let current = '';

  for (const sentence of sentences) {
    const candidate = current + sentence;
    if (Buffer.byteLength(candidate) <= MAX_CHUNK_BYTES) {
      current = candidate;
      continue;
    }
    if (current) chunks.push(current.trim());
    // Oración más larga que el límite: se corta por palabras.
    if (Buffer.byteLength(sentence) > MAX_CHUNK_BYTES) {
      let piece = '';
      for (const word of sentence.split(/\s+/)) {
        if (Buffer.byteLength(`${piece} ${word}`) > MAX_CHUNK_BYTES) {
          chunks.push(piece.trim());
          piece = word;
        } else {
          piece = piece ? `${piece} ${word}` : word;
        }
      }
      current = piece;
    } else {
      current = sentence;
    }
  }
  if (current.trim()) chunks.push(current.trim());
  return chunks;
}

async function translateChunk(chunk: string, from: string, to: string): Promise<string | null> {
  const params = new URLSearchParams({ q: chunk, langpair: `${from}|${to}` });
  if (env.TRANSLATE_EMAIL) params.set('de', env.TRANSLATE_EMAIL);

  const res = await fetch(`${ENDPOINT}?${params}`, { signal: AbortSignal.timeout(8000) });
  if (!res.ok) return null;

  const json = (await res.json()) as {
    responseStatus?: number | string;
    responseData?: { translatedText?: string };
    quotaFinished?: boolean;
  };
  const text = json.responseData?.translatedText ?? '';

  if (
    json.quotaFinished ||
    Number(json.responseStatus) === 429 ||
    text.startsWith('MYMEMORY WARNING')
  ) {
    quotaExhausted = true;
    logger.warn('Cupo diario de MyMemory agotado: las sinopsis restantes quedan en inglés');
    return null;
  }
  if (Number(json.responseStatus) !== 200 || !text) return null;
  return text;
}

export async function translateText(text: string, from = 'en', to = 'es'): Promise<string | null> {
  const clean = text.trim();
  if (!clean || quotaExhausted) return null;

  try {
    const parts: string[] = [];
    for (const chunk of splitIntoChunks(clean)) {
      const translated = await translateChunk(chunk, from, to);
      if (translated == null) return null;
      parts.push(translated);
    }
    return parts.join(' ');
  } catch (err) {
    logger.warn({ err }, 'Falló la traducción automática');
    return null;
  }
}
