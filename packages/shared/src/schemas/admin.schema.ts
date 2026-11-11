import { z } from 'zod';
import { ROLES } from '../constants/roles';

export const rejectMovieSchema = z.object({
  reason: z.string().trim().min(3, 'Debes especificar un motivo de rechazo de al menos 3 caracteres'),
});

export const updateUserRoleSchema = z.object({
  role: z.enum(ROLES, { message: 'Rol inválido' }),
});

export type RejectMovieInput = z.infer<typeof rejectMovieSchema>;
export type UpdateUserRoleInput = z.infer<typeof updateUserRoleSchema>;

