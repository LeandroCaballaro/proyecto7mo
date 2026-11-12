import 'dotenv/config';
import { z } from 'zod';

const booleanString = z
  .enum(['true', 'false', '1', '0', ''])
  .default('false')
  .transform((v) => v === 'true' || v === '1');

const envSchema = z.object({
  NODE_ENV: z.enum(['development', 'test', 'production']).default('development'),
  PORT: z.coerce.number().int().positive().default(4000),
  CLIENT_URLS: z
    .string()
    .default('http://localhost:5173')
    .transform((v) =>
      v
        .split(',')
        .map((s) => s.trim().replace(/\/$/, ''))
        .filter(Boolean),
    ),
  APP_URL: z.url().default('http://localhost:5173'),
  TRUST_PROXY: z.coerce.number().int().min(0).default(0),

  DATABASE_URL: z.string().min(1, 'DATABASE_URL es obligatoria'),

  JWT_ACCESS_SECRET: z.string().min(32, 'JWT_ACCESS_SECRET debe tener al menos 32 caracteres'),
  ACCESS_TOKEN_TTL_SECONDS: z.coerce.number().int().positive().default(900),
  REFRESH_TOKEN_TTL_DAYS: z.coerce.number().int().positive().default(7),
  PASSWORD_RESET_TTL_MINUTES: z.coerce.number().int().positive().default(30),
  COOKIE_SECURE: booleanString,
  COOKIE_DOMAIN: z
    .string()
    .optional()
    .transform((v) => (v ? v : undefined)),

  SMTP_HOST: z.string().optional().default(''),
  SMTP_PORT: z.coerce.number().int().positive().default(1025),
  SMTP_USER: z.string().optional().default(''),
  SMTP_PASS: z.string().optional().default(''),
  MAIL_FROM: z.string().default('NexoHub <no-reply@nexohub.local>'),

  /** API key v3 o token de lectura v4 de TMDB. Vacío = se usa Cinemeta (en inglés, se traduce). */
  TMDB_API_KEY: z.string().trim().optional().default(''),
  /** Idioma principal pedido a TMDB. es-MX tiene la mejor cobertura para Latinoamérica. */
  TMDB_LANGUAGE: z.string().trim().default('es-MX'),
  /** Email para MyMemory: sube el cupo gratuito de traducción de 5.000 a 50.000 caracteres/día. */
  TRANSLATE_EMAIL: z.string().trim().optional().default(''),
  /** Películas nuevas a importar por día desde el servidor (0 = desactivado). */
  AUTO_IMPORT_MOVIES: z.coerce.number().int().min(0).max(200).default(0),
  /** Años hacia atrás que recorre la importación automática. */
  AUTO_IMPORT_YEARS: z.coerce.number().int().min(1).max(10).default(2),
});

const parsed = envSchema.safeParse(process.env);

if (!parsed.success) {
  console.error('Variables de entorno inválidas:');
  for (const issue of parsed.error.issues) {
    console.error(`  - ${issue.path.join('.')}: ${issue.message}`);
  }
  process.exit(1);
}

export const env = parsed.data;
export const isProd = env.NODE_ENV === 'production';
export const isTest = env.NODE_ENV === 'test';
