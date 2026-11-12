/**
 * Cuentas personales definidas en apps/api/.env (no se versionan):
 *   SUPERADMIN_NAME / SUPERADMIN_USERNAME / SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD
 *   TEST_USER_NAME  / TEST_USER_USERNAME  / TEST_USER_EMAIL  / TEST_USER_PASSWORD
 *
 *   npm run db:accounts -w @nexohub/api
 *
 * Idempotente: si la cuenta existe (por email) actualiza nombre, usuario, rol y contraseña.
 */
import 'dotenv/config';
import { PrismaClient, type Role } from '@prisma/client';
import { emailField, nameField, passwordField, usernameField } from '@nexohub/shared';
import { z } from 'zod';
import { hashPassword } from '../../src/lib/password';

const accountSchema = z.object({
  name: nameField,
  username: usernameField,
  email: emailField,
  password: passwordField,
});

function readAccount(prefix: string): z.infer<typeof accountSchema> | null {
  const raw = {
    name: process.env[`${prefix}_NAME`],
    username: process.env[`${prefix}_USERNAME`],
    email: process.env[`${prefix}_EMAIL`],
    password: process.env[`${prefix}_PASSWORD`],
  };
  if (!raw.email) return null;

  const parsed = accountSchema.safeParse(raw);
  if (!parsed.success) {
    const issues = parsed.error.issues.map(
      (i) => `${prefix}_${String(i.path[0]).toUpperCase()}: ${i.message}`,
    );
    throw new Error(`Datos inválidos en .env:\n  - ${issues.join('\n  - ')}`);
  }
  return parsed.data;
}

export async function upsertPersonalAccounts(prisma: PrismaClient): Promise<string[]> {
  const accounts: [string, Role][] = [
    ['SUPERADMIN', 'SUPERADMIN'],
    ['TEST_USER', 'USER'],
  ];
  const done: string[] = [];

  for (const [prefix, role] of accounts) {
    const account = readAccount(prefix);
    if (!account) continue;

    const clash = await prisma.user.findFirst({
      where: { username: account.username, NOT: { email: account.email } },
    });
    if (clash)
      throw new Error(
        `${prefix}_USERNAME "${account.username}" ya lo usa otra cuenta (${clash.email})`,
      );

    const passwordHash = await hashPassword(account.password);
    await prisma.user.upsert({
      where: { email: account.email },
      update: { name: account.name, username: account.username, role, passwordHash },
      create: {
        name: account.name,
        username: account.username,
        email: account.email,
        role,
        passwordHash,
      },
    });
    done.push(`${role.padEnd(10)} ${account.username} <${account.email}>`);
  }
  return done;
}

// Ejecución directa: `tsx prisma/seed/accounts.ts`
if (process.argv[1]?.replace(/\\/g, '/').endsWith('prisma/seed/accounts.ts')) {
  const prisma = new PrismaClient();
  upsertPersonalAccounts(prisma)
    .then((done) => {
      if (done.length === 0)
        console.log('No hay cuentas definidas en .env (SUPERADMIN_EMAIL / TEST_USER_EMAIL).');
      for (const line of done) console.log(`  Cuenta lista: ${line}`);
    })
    .catch((err) => {
      console.error((err as Error).message);
      process.exitCode = 1;
    })
    .finally(() => prisma.$disconnect());
}
