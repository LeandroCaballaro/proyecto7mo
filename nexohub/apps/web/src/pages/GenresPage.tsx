import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Film } from 'lucide-react';
import { GENRES } from '@nexohub/shared';
import { Skeleton } from '../components/ui';
import { apiClient } from '../lib/api-client';

interface GenreWithCount {
  id: number;
  name: string;
  slug: string;
  moviesCount: number;
}

export function GenresPage() {
  const { data: genres, isLoading } = useQuery({
    queryKey: ['movies', 'genres'],
    queryFn: async () => {
      const res = await apiClient<{ data: GenreWithCount[] }>('/movies/genres');
      return res.data;
    },
  });

  const list = genres && genres.length > 0 ? genres : GENRES.map((g) => ({ ...g, id: 0, moviesCount: 0 }));

  return (
    <div className="space-y-8 pb-16">
      <div className="border-b border-[#0f3460]/60 pb-6">
        <div className="flex items-center gap-2">
          <Film className="h-6 w-6 text-[#e94560]" />
          <h1 className="text-2xl sm:text-3xl font-black text-[#f5f5f5]">Géneros Cinematográficos</h1>
        </div>
        <p className="text-xs sm:text-sm text-[#a0a0a0] mt-1">
          Explora las diferentes categorías temáticas del catálogo de NexoHub con su volumen real de películas.
        </p>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
          {Array.from({ length: 8 }).map((_, i) => (
            <Skeleton key={i} className="h-40 w-full rounded-2xl" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
          {list.map((g) => (
            <Link
              key={g.slug}
              to={`/explorar?genre=${g.slug}`}
              className="p-6 rounded-2xl bg-[#16213e] border border-[#0f3460] hover:border-[#e94560] hover:shadow-lg hover:shadow-[#e94560]/5 transition-all duration-200 group flex flex-col justify-between h-40 shadow-sm"
            >
              <div className="p-3 w-fit rounded-xl bg-[#0f172a] border border-[#0f3460] text-[#e94560] group-hover:scale-110 transition-transform">
                <Film className="h-5 w-5" />
              </div>

              <div>
                <h3 className="text-base font-bold text-[#f5f5f5] group-hover:text-[#e94560] transition-colors">
                  {g.name}
                </h3>
                <p className="text-xs text-[#a0a0a0] mt-0.5">
                  {g.moviesCount !== undefined
                    ? `${g.moviesCount} ${g.moviesCount === 1 ? 'película disponible' : 'películas disponibles'}`
                    : 'Explorar películas'}
                </p>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}

