import bcrypt from 'bcryptjs';
import { describe, expect, it } from 'vitest';
import { hashPassword, verifyPassword } from '../src/lib/password';

describe('password', () => {
  it('hashea con argon2id y verifica', async () => {
    const hash = await hashPassword('Secreta123');
    expect(hash.startsWith('$argon2id$')).toBe(true);
    expect(await verifyPassword(hash, 'Secreta123')).toEqual({ valid: true, needsRehash: false });
    expect((await verifyPassword(hash, 'otra')).valid).toBe(false);
  });

  it('acepta hashes bcrypt $2y$ de PHP y pide re-hash', async () => {
    // Hash real de 002_seed_users.sql para "password"
    const php = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    expect(await verifyPassword(php, 'password')).toEqual({ valid: true, needsRehash: true });
    expect(await verifyPassword(php, 'Password')).toEqual({ valid: false, needsRehash: false });
  });

  it('acepta bcrypt $2b$ generado por Node', async () => {
    const hash = await bcrypt.hash('abc', 4);
    expect((await verifyPassword(hash, 'abc')).valid).toBe(true);
  });

  it('rechaza formatos desconocidos', async () => {
    expect((await verifyPassword('md5:abc', 'abc')).valid).toBe(false);
  });
});

