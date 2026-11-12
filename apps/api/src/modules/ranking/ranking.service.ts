import type { RankingUserDto } from '@nexohub/shared';
import { prisma } from '../../lib/prisma';
import { AppError } from '../../utils/AppError';

export const rankingService = {
  async getGlobal(limit = 20): Promise<RankingUserDto[]> {
    const users = await prisma.user.findMany({
      where: {
        isPublic: true,
        reputation: { gt: 0 },
      },
      orderBy: [{ reputation: 'desc' }, { name: 'asc' }],
      take: limit,
      select: {
        id: true,
        name: true,
        username: true,
        avatarUrl: true,
        reputation: true,
      },
    });

    return users.map((u) => ({
      id: u.id,
      name: u.name,
      username: u.username,
      avatarUrl: u.avatarUrl,
      reputation: u.reputation,
    }));
  },

  async getByGenre(genreSlug: string, limit = 20): Promise<RankingUserDto[]> {
    const genre = await prisma.genre.findUnique({
      where: { slug: genreSlug },
    });
    if (!genre) throw AppError.notFound('Género no encontrado');

    const ranking = await prisma.genreReputation.findMany({
      where: {
        genreId: genre.id,
        points: { gt: 0 },
        user: { isPublic: true },
      },
      orderBy: [{ points: 'desc' }, { user: { name: 'asc' } }],
      take: limit,
      include: {
        user: {
          select: {
            id: true,
            name: true,
            username: true,
            avatarUrl: true,
            reputation: true,
          },
        },
      },
    });

    return ranking.map((r) => ({
      id: r.user.id,
      name: r.user.name,
      username: r.user.username,
      avatarUrl: r.user.avatarUrl,
      reputation: r.user.reputation,
      points: r.points,
      genre: genre.name,
    }));
  },
};

