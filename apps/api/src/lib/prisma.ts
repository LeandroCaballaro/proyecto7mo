import { PrismaClient } from '@prisma/client';
import { isProd } from '../config/env';

const globalForPrisma = globalThis as unknown as { prisma?: PrismaClient };

export const prisma =
  globalForPrisma.prisma ??
  new PrismaClient({
    log: isProd ? ['error'] : ['warn', 'error'],
  });

// Evita múltiples conexiones al recargar con `tsx watch`.
if (!isProd) globalForPrisma.prisma = prisma;

export type TxClient = Omit<
  PrismaClient,
  '$connect' | '$disconnect' | '$on' | '$transaction' | '$extends'
>;

