import { z } from 'zod';
import {
  ALLOWED_EMAIL_DOMAINS,
  NAME_MAX,
  PASSWORD_MAX,
  PASSWORD_MIN,
  USERNAME_MAX,
  USERNAME_MIN,
} from '../constants/rules';

// ───────────────────────────── Campos reutilizables ─────────────────────────────

export const nameField = z
  .string()
  .trim()
  .min(1, 'El nombre es obligatorio')
  .max(NAME_MAX, `El nombre no puede superar los ${NAME_MAX} caracteres`)
  .regex(/^[\p{L}\p{N} ]+$/u, 'El nombre no puede tener caracteres especiales');

export const usernameField = z
  .string()
  .trim()
  .min(USERNAME_MIN, `El usuario debe tener al menos ${USERNAME_MIN} caracteres`)
  .max(USERNAME_MAX, `El usuario no puede superar los ${USERNAME_MAX} caracteres`)
  .regex(/^[A-Za-z0-9]+$/, 'El usuario solo puede tener letras y números');

export const emailField = z
  .string()
  .trim()
  .toLowerCase()
  .pipe(z.email('El email no es válido'));

/** Email de registro: además exige un dominio conocido. */
export const registrationEmailField = emailField.refine(
  (email) => (ALLOWED_EMAIL_DOMAINS as readonly string[]).includes(email.split('@')[1] ?? ''),
  `Solo se permiten correos de: ${ALLOWED_EMAIL_DOMAINS.join(', ')}`,
);

export const passwordField = z
  .string()
  .min(PASSWORD_MIN, `La contraseña debe tener al menos ${PASSWORD_MIN} caracteres`)
  .max(PASSWORD_MAX, `La contraseña no puede superar los ${PASSWORD_MAX} caracteres`)
  .regex(/[A-Z]/, 'Debe incluir al menos una mayúscula')
  .regex(/[a-z]/, 'Debe incluir al menos una minúscula')
  .regex(/[0-9]/, 'Debe incluir al menos un número');

const passwordsMatch = <T extends { password: string; confirmPassword: string }>(data: T) =>
  data.password === data.confirmPassword;

const mismatch = { message: 'Las contraseñas no coinciden', path: ['confirmPassword'] };

// ───────────────────────────── Esquemas de request ─────────────────────────────

export const registerSchema = z
  .object({
    name: nameField,
    username: usernameField,
    email: registrationEmailField,
    password: passwordField,
    confirmPassword: z.string(),
  })
  .refine(passwordsMatch, mismatch);

export const loginSchema = z.object({
  /** Email o nombre de usuario. */
  identifier: z.string().trim().min(1, 'Ingresá tu email o usuario').max(255),
  password: z.string().min(1, 'Ingresá tu contraseña').max(PASSWORD_MAX),
});

export const forgotPasswordSchema = z.object({
  email: emailField,
});

export const resetPasswordSchema = z
  .object({
    token: z.string().min(32).max(128),
    password: passwordField,
    confirmPassword: z.string(),
  })
  .refine(passwordsMatch, mismatch);

export const changePasswordSchema = z
  .object({
    currentPassword: z.string().min(1, 'Ingresá tu contraseña actual').max(PASSWORD_MAX),
    password: passwordField,
    confirmPassword: z.string(),
  })
  .refine(passwordsMatch, mismatch)
  .refine((d) => d.currentPassword !== d.password, {
    message: 'La nueva contraseña debe ser distinta de la actual',
    path: ['password'],
  });

export type RegisterInput = z.infer<typeof registerSchema>;
export type LoginInput = z.infer<typeof loginSchema>;
export type ForgotPasswordInput = z.infer<typeof forgotPasswordSchema>;
export type ResetPasswordInput = z.infer<typeof resetPasswordSchema>;
export type ChangePasswordInput = z.infer<typeof changePasswordSchema>;

