import type { Role } from '@prisma/client';
import type { MovieDto } from '@nexohub/shared';
import { prisma } from '../../lib/prisma';
import { AppError } from '../../utils/AppError';
import { toAuthUser, toPublicUser } from '../users/users.mapper';

export const adminService = {
  async listPendingMovies(): Promise<MovieDto[]> {
    const movies = await prisma.movie.findMany({
      where: { status: 'PENDING' },
      orderBy: { createdAt: 'desc' },
      include: {
        genre: true,
        proposedBy: true,
      },
    });

    return movies.map((m) => ({
      id: m.id,
      title: m.title,
      synopsis: m.synopsis,
      director: m.director,
      releaseYear: m.releaseYear,
      posterUrl: m.posterUrl,
      featured: m.featured,
      status: m.status,
      rejectionReason: m.rejectionReason,
      ratingAvg: Number(m.ratingAvg),
      reviewsCount: m.reviewsCount,
      genre: {
        id: m.genre.id,
        name: m.genre.name,
        slug: m.genre.slug,
      },
      proposedBy: m.proposedBy ? toPublicUser(m.proposedBy) : null,
      createdAt: m.createdAt.toISOString(),
    }));
  },

  async approveMovie(movieId: number, moderatorId: number) {
    const movie = await prisma.movie.findUnique({ where: { id: movieId } });
    if (!movie) throw AppError.notFound('Película no encontrada');

    const updated = await prisma.movie.update({
      where: { id: movieId },
      data: {
        status: 'APPROVED',
        rejectionReason: null,
        moderatedById: moderatorId,
        moderatedAt: new Date(),
      },
      include: { genre: true },
    });

    return {
      id: updated.id,
      title: updated.title,
      status: updated.status,
    };
  },

  async rejectMovie(movieId: number, moderatorId: number, reason: string) {
    const movie = await prisma.movie.findUnique({ where: { id: movieId } });
    if (!movie) throw AppError.notFound('Película no encontrada');

    const updated = await prisma.movie.update({
      where: { id: movieId },
      data: {
        status: 'REJECTED',
        rejectionReason: reason,
        moderatedById: moderatorId,
        moderatedAt: new Date(),
      },
      include: { genre: true },
    });

    return {
      id: updated.id,
      title: updated.title,
      status: updated.status,
      rejectionReason: updated.rejectionReason,
    };
  },

  async listUsers() {
    const users = await prisma.user.findMany({
      orderBy: [{ role: 'desc' }, { createdAt: 'desc' }],
    });

    return users.map(toAuthUser);
  },

  async updateUserRole(targetUserId: number, newRole: Role, actorUserId: number) {
    const actor = await prisma.user.findUnique({ where: { id: actorUserId } });
    if (!actor || actor.role !== 'SUPERADMIN') {
      throw AppError.forbidden('Solo un Superadministrador puede modificar roles de usuario');
    }

    if (targetUserId === actorUserId && newRole !== 'SUPERADMIN') {
      throw AppError.badRequest('No puedes remover tu propio rol de Superadministrador');
    }

    const updated = await prisma.user.update({
      where: { id: targetUserId },
      data: { role: newRole },
    });

    return toAuthUser(updated);
  },
};

