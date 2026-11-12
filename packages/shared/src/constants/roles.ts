export const ROLES = ['USER', 'ADMIN', 'SUPERADMIN'] as const;

export type Role = (typeof ROLES)[number];

export const STAFF_ROLES: readonly Role[] = ['ADMIN', 'SUPERADMIN'];

export const isStaff = (role: Role | null | undefined): boolean =>
  role != null && STAFF_ROLES.includes(role);

