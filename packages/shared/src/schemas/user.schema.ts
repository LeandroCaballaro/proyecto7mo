import { z } from 'zod';
import { BIO_MAX, NAME_MAX } from '../constants/rules';

export const updateProfileSchema = z.object({
  name: z
    .string()
    .trim()
    .min(1, 'El nombre es obligatorio')
    .max(NAME_MAX, `El nombre no puede superar ${NAME_MAX} caracteres`)
    .regex(/^[\p{L}\p{N} ]+$/u, 'El nombre no puede tener caracteres especiales')
    .optional(),
  username: z
    .string()
    .trim()
    .min(3, 'El nombre de usuario debe tener al menos 3 caracteres')
    .max(20, 'El nombre de usuario no puede superar los 20 caracteres')
    .regex(/^[A-Za-z0-9_]+$/, 'El nombre de usuario solo puede tener letras, números y guión bajo')
    .optional(),
  bio: z.string().trim().max(BIO_MAX, `La biografía no puede superar los ${BIO_MAX} caracteres`).optional(),
  avatarUrl: z.string().optional().nullable(),
  isPublic: z.boolean().optional(),
});

export type UpdateProfileInput = z.infer<typeof updateProfileSchema>;
