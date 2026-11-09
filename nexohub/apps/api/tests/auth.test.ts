import request from 'supertest';
import { afterAll, beforeEach, describe, expect, it } from 'vitest';
import { createApp } from '../src/app';
import { prisma } from '../src/lib/prisma';
import { generateOpaqueToken, sha256 } from '../src/lib/tokens';

const app = createApp();
const API = '/api/v1/auth';

const valid = {
  name: 'Juan Perez',
  username: 'juanp',
  email: 'juan@gmail.com',
  password: 'Segura123',
  confirmPassword: 'Segura123',
};

/** Extrae `nombre=valor` de los Set-Cookie para reenviarlos. */
function cookiesOf(res: request.Response): Record<string, string> {
  const raw = ([] as string[]).concat(res.headers['set-cookie'] ?? []);
  return Object.fromEntries(
    raw.map((c) => c.split(';')[0]!.split('=') as [string, string]).filter(([, v]) => v !== ''),
  );
}

const cookieHeader = (c: Record<string, string>) =>
  Object.entries(c)
    .map(([k, v]) => `${k}=${v}`)
    .join('; ');

async function cleanDb() {
  await prisma.$transaction([
    prisma.refreshToken.deleteMany(),
    prisma.passwordResetToken.deleteMany(),
    prisma.user.deleteMany(),
  ]);
}

beforeEach(cleanDb);
afterAll(async () => {
  await cleanDb();
  await prisma.$disconnect();
});

describe('POST /auth/register', () => {
  it('crea la cuenta, setea cookies httpOnly y no expone el hash', async () => {
    const res = await request(app).post(`${API}/register`).send(valid);

    expect(res.status).toBe(201);
    expect(res.body.data.user).toMatchObject({ username: 'juanp', email: 'juan@gmail.com', role: 'USER' });
    expect(res.body.data.user.passwordHash).toBeUndefined();

    const setCookie = ([] as string[]).concat(res.headers['set-cookie'] ?? []);
    expect(setCookie.find((c) => c.startsWith('nh_at='))).toMatch(/HttpOnly/i);
    expect(setCookie.find((c) => c.startsWith('nh_rt='))).toMatch(/Path=\/api\/v1\/auth/);
  });

  it('valida reglas de negocio con detalle por campo', async () => {
    const res = await request(app)
      .post(`${API}/register`)
      .send({ ...valid, email: 'x@empresa.com', password: 'debil', confirmPassword: 'otra' });

    expect(res.status).toBe(422);
    const paths = res.body.error.details.map((d: { path: string }) => d.path);
    expect(paths).toEqual(expect.arrayContaining(['body.email', 'body.password']));
  });

  it('devuelve 409 indicando qué campo está tomado', async () => {
    await request(app).post(`${API}/register`).send(valid);
    const res = await request(app)
      .post(`${API}/register`)
      .send({ ...valid, email: 'otro@gmail.com' });

    expect(res.status).toBe(409);
    expect(res.body.error.details).toEqual([{ path: 'username', message: expect.any(String) }]);
  });
});

describe('POST /auth/login + GET /auth/me', () => {
  beforeEach(async () => {
    await request(app).post(`${API}/register`).send(valid);
  });

  it.each([['juanp'], ['JUAN@gmail.com']])('permite login con %s', async (identifier) => {
    const res = await request(app).post(`${API}/login`).send({ identifier, password: valid.password });
    expect(res.status).toBe(200);

    const me = await request(app).get(`${API}/me`).set('Cookie', cookieHeader(cookiesOf(res)));
    expect(me.status).toBe(200);
    expect(me.body.data.user.username).toBe('juanp');
  });

  it('responde lo mismo para usuario inexistente y contraseña incorrecta', async () => {
    const a = await request(app).post(`${API}/login`).send({ identifier: 'juanp', password: 'Mala1234' });
    const b = await request(app).post(`${API}/login`).send({ identifier: 'nadie', password: 'Mala1234' });
    expect(a.status).toBe(401);
    expect(b.status).toBe(401);
    expect(a.body).toEqual(b.body);
  });

  it('GET /me sin sesión devuelve 401 UNAUTHENTICATED', async () => {
    const res = await request(app).get(`${API}/me`);
    expect(res.status).toBe(401);
    expect(res.body.error.code).toBe('UNAUTHENTICATED');
  });
});

describe('Migración de hashes legacy', () => {
  it('acepta bcrypt de PHP y lo convierte a argon2id', async () => {
    await prisma.user.create({
      data: {
        name: 'Legacy',
        username: 'legacy',
        email: 'legacy@nexohub.local',
        passwordHash: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
      },
    });

    const res = await request(app).post(`${API}/login`).send({ identifier: 'legacy', password: 'password' });
    expect(res.status).toBe(200);

    const user = await prisma.user.findUniqueOrThrow({ where: { username: 'legacy' } });
    expect(user.passwordHash.startsWith('$argon2id$')).toBe(true);
  });
});

describe('POST /auth/refresh (rotación)', () => {
  it('rota el refresh token y detecta reutilización', async () => {
    const reg = await request(app).post(`${API}/register`).send(valid);
    const first = cookiesOf(reg);

    const r1 = await request(app).post(`${API}/refresh`).set('Cookie', cookieHeader(first));
    expect(r1.status).toBe(200);
    const second = cookiesOf(r1);
    expect(second.nh_rt).toBeDefined();
    expect(second.nh_rt).not.toBe(first.nh_rt);

    // Dentro de la ventana de gracia: carrera benigna, no castiga.
    const race = await request(app).post(`${API}/refresh`).set('Cookie', cookieHeader(first));
    expect(race.body.error.code).toBe('REFRESH_RACE');

    // Fuera de la ventana: se considera robo y se revoca toda la familia.
    await prisma.refreshToken.updateMany({
      where: { tokenHash: sha256(first.nh_rt!) },
      data: { revokedAt: new Date(Date.now() - 60_000) },
    });
    const reuse = await request(app).post(`${API}/refresh`).set('Cookie', cookieHeader(first));
    expect(reuse.status).toBe(401);
    expect(reuse.body.error.code).toBe('REFRESH_REUSED');

    // El token legítimo más reciente también quedó invalidado.
    const after = await request(app).post(`${API}/refresh`).set('Cookie', cookieHeader(second));
    expect(after.status).toBe(401);
  });

  it('logout revoca el refresh token', async () => {
    const reg = await request(app).post(`${API}/register`).send(valid);
    const c = cookieHeader(cookiesOf(reg));

    expect((await request(app).post(`${API}/logout`).set('Cookie', c)).status).toBe(204);
    const res = await request(app).post(`${API}/refresh`).set('Cookie', c);
    expect(res.status).toBe(401);
  });
});

describe('Recuperación de contraseña', () => {
  it('forgot-password responde igual exista o no el email', async () => {
    await request(app).post(`${API}/register`).send(valid);
    const a = await request(app).post(`${API}/forgot-password`).send({ email: valid.email });
    const b = await request(app).post(`${API}/forgot-password`).send({ email: 'nadie@gmail.com' });
    expect(a.status).toBe(200);
    expect(a.body).toEqual(b.body);
    expect(await prisma.passwordResetToken.count()).toBe(1);
  });

  it('reset-password cambia la contraseña, cierra sesiones y no se puede reutilizar', async () => {
    const reg = await request(app).post(`${API}/register`).send(valid);
    const session = cookieHeader(cookiesOf(reg));
    const user = await prisma.user.findUniqueOrThrow({ where: { username: 'juanp' } });

    const token = generateOpaqueToken();
    await prisma.passwordResetToken.create({
      data: { userId: user.id, tokenHash: sha256(token), expiresAt: new Date(Date.now() + 60_000) },
    });

    const body = { token, password: 'NuevaClave9', confirmPassword: 'NuevaClave9' };
    expect((await request(app).post(`${API}/reset-password`).send(body)).status).toBe(200);
    expect((await request(app).post(`${API}/reset-password`).send(body)).status).toBe(400);

    expect((await request(app).post(`${API}/refresh`).set('Cookie', session)).status).toBe(401);
    const login = await request(app)
      .post(`${API}/login`)
      .send({ identifier: 'juanp', password: 'NuevaClave9' });
    expect(login.status).toBe(200);
  });
});

describe('Seguridad HTTP', () => {
  it('rechaza mutaciones desde un Origin no permitido', async () => {
    const res = await request(app).post(`${API}/login`).set('Origin', 'https://malicioso.com').send({});
    expect(res.status).toBe(403);
  });

  it('JSON mal formado devuelve 400 con el formato de error estándar', async () => {
    const res = await request(app)
      .post(`${API}/login`)
      .set('Content-Type', 'application/json')
      .send('{"identifier":');
    expect(res.status).toBe(400);
    expect(res.body.error.code).toBe('BAD_REQUEST');
  });
});
