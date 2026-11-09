import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Compass, Heart } from 'lucide-react';
import type { MovieDto } from '@nexohub/shared';
import { Button, Skeleton } from '../components/ui';
import { MovieCard } from '../features/movies/MovieCard';
import { apiClient } from '../lib/api-client';

export function FavoritesPage() {
  const { data: favorites, isLoading } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => {
      const res = await apiClient<{ data: MovieDto[] }>('/users/me/favorites');
      return res.data;
    },
  });

  return (
    <div className="space-y-8 pb-16">
      <div className="border-b border-[#0f3460]/60 pb-6">
        <div className="flex items-center gap-2">
          <Heart className="h-6 w-6 text-[#e94560] fill-[#e94560]" />
          <h1 className="text-2xl sm:text-3xl font-black text-[#f5f5f5]">Mis Películas Favoritas</h1>
        </div>
        <p className="text-xs sm:text-sm text-[#a0a0a0] mt-1">
          Las obras cinematográficas que guardaste en tu colección personal.
        </p>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="space-y-3">
              <Skeleton className="aspect-[2/3] w-full rounded-2xl" />
              <Skeleton className="h-4 w-3/4 rounded-lg" />
              <Skeleton className="h-3 w-1/2 rounded-lg" />
            </div>
          ))}
        </div>
      ) : favorites && favorites.length > 0 ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
          {favorites.map((movie) => (
            <MovieCard key={movie.id} movie={movie} />
          ))}
        </div>
      ) : (
        <div className="py-20 text-center rounded-2xl bg-[#16213e] border border-[#0f3460] space-y-3 shadow-lg">
          <Heart className="h-10 w-10 text-[#a0a0a0] mx-auto opacity-40" />
          <h3 className="text-base font-semibold text-white">Aún no tienes favoritas</h3>
          <p className="text-xs text-[#a0a0a0] max-w-sm mx-auto">
            Explora el catálogo y pulsa el corazón en cualquier película para guardarla aquí.
          </p>
          <Link to="/explorar">
            <Button variant="primary" size="sm" leftIcon={<Compass className="h-4 w-4" />}>
              Ir a Explorar
            </Button>
          </Link>
        </div>
      )}
    </div>
  );
}

