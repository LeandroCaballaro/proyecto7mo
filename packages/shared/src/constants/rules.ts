/** Reglas de negocio compartidas entre cliente y servidor. */

export const RATING_MIN = 1;
export const RATING_MAX = 5;

/** Cada N likes recibidos en reseñas de un género = 1 punto de reputación en ese género. */
export const LIKES_PER_POINT = 10;

export const NAME_MAX = 40;
export const USERNAME_MIN = 3;
export const USERNAME_MAX = 20;
export const BIO_MAX = 100;
export const PASSWORD_MIN = 8;
/** Límite práctico para evitar DoS con hashes sobre entradas enormes. */
export const PASSWORD_MAX = 128;

/** Dominios permitidos al registrarse. */
export const ALLOWED_EMAIL_DOMAINS = [
  'gmail.com',
  'hotmail.com',
  'hotmail.com.ar',
  'live.com',
  'live.com.ar',
  'outlook.com',
  'outlook.com.ar',
  'yahoo.com',
  'yahoo.com.ar',
  'icloud.com',
  'proton.me',
] as const;
