import { execSync } from 'node:child_process';

/**
 * Antes de correr los tests: crea (o recrea) la base de pruebas y aplica las migraciones.
 * Solo actúa sobre bases cuyo nombre termina en `_test`, para no borrar nunca la de desarrollo.
 */
export default function setup() {
  const url = process.env.TEST_DATABASE_URL ?? 'mysql://root:@127.0.0.1:3306/nexohub_test';
  const dbName = new URL(url).pathname.replace(/^\//, '');

  if (!dbName.endsWith('_test')) {
    throw new Error(`TEST_DATABASE_URL debe apuntar a una base *_test (recibido: "${dbName}")`);
  }

  execSync('npx prisma migrate reset --force --skip-seed --skip-generate', {
    stdio: 'inherit',
    env: { ...process.env, DATABASE_URL: url, PRISMA_HIDE_UPDATE_MESSAGE: '1' },
  });
}
