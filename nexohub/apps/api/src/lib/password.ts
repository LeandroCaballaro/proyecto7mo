import { hash, verify, type Algorithm } from '@node-rs/argon2';
import bcrypt from 'bcryptjs';

/**
 * Parámetros argon2id (recomendación OWASP 2024: m=19 MiB, t=2, p=1).
 * Si se endurecen, `needsRehash` detecta los hashes viejos y se re-hashean al hacer login.
 */
const ARGON2_OPTIONS = {
  // `Algorithm` es un const enum ambiental (no usable con verbatimModuleSyntax): 2 = Argon2id.
  algorithm: 2 as Algorithm,
  memoryCost: 19_456,
  timeCost: 2,
  parallelism: 1,
} as const;

const LEGACY_BCRYPT = /^\$2[abxy]\$\d{2}\$/;

export function hashPassword(plain: string): Promise<string> {
  return hash(plain, ARGON2_OPTIONS);
}

export interface PasswordCheck {
  valid: boolean;
  /** true si el hash es legacy (bcrypt de PHP) o usa parámetros argon2 desactualizados. */
  needsRehash: boolean;
}

export async function verifyPassword(storedHash: string, plain: string): Promise<PasswordCheck> {
  if (LEGACY_BCRYPT.test(storedHash)) {
    // PHP genera `$2y$`, que es algorítmicamente idéntico a `$2a$`/`$2b$`.
    const normalized = storedHash.replace(/^\$2y\$/, '$2b$');
    const valid = await bcrypt.compare(plain, normalized);
    return { valid, needsRehash: valid };
  }

  if (storedHash.startsWith('$argon2')) {
    const valid = await verify(storedHash, plain).catch(() => false);
    return { valid, needsRehash: valid && argonParamsOutdated(storedHash) };
  }

  return { valid: false, needsRehash: false };
}

/**
 * Hash ficticio para igualar el tiempo de respuesta cuando el usuario no existe
 * (evita enumerar cuentas por timing).
 */
let dummyHash: Promise<string> | undefined;
export async function burnPasswordCheck(plain: string): Promise<void> {
  dummyHash ??= hashPassword('nexohub-timing-equalizer');
  await verify(await dummyHash, plain).catch(() => false);
}

function argonParamsOutdated(stored: string): boolean {
  // Formato: $argon2id$v=19$m=19456,t=2,p=1$salt$hash
  const match = /\$m=(\d+),t=(\d+),p=(\d+)\$/.exec(stored);
  if (!stored.startsWith('$argon2id$') || !match) return true;
  const [, m, t, p] = match.map(Number);
  return (
    m !== ARGON2_OPTIONS.memoryCost ||
    t !== ARGON2_OPTIONS.timeCost ||
    p !== ARGON2_OPTIONS.parallelism
  );
}
