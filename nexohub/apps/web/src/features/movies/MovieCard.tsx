import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Calendar, Heart } from 'lucide-react';
import { toast } from 'sonner';
import type { MovieDto } from '@nexohub/shared';
import { Badge, StarRating } from '../../components/ui';
import { apiClient } from '../../lib/api-client';
import { useAuthStore } from '../../stores/auth.store';

export function MovieCard({ movie }: { movie: MovieDto }) {
  const queryClient = useQueryClient();
  const { user } = useAuthStore();
  const [isFav, setIsFav] = useState(Boolean(movie.isFavorite));

  const toggleFavoriteMutation = useMutation({
    mutationFn: async () => {
      if (!isFav) {
        return apiClient(`/users/me/favorites/${movie.id}`, { method: 'PUT' });
      } else {
        return apiClient(`/users/me/favorites/${movie.id}`, { method: 'DELETE' });
      }
    },
    onMutate: () => {
      setIsFav((prev) => !prev);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['favorites'] });
    },
    onError: () => {
      setIsFav((prev) => !prev);
      toast.error('No se pudo actualizar favoritos');
    },
  });

  function handleHeartClick(e: React.MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    if (!user) {
      toast.error('Debes iniciar sesión para guardar favoritos');
      return;
    }
    toggleFavoriteMutation.mutate();
  }

  const fallbackPoster =
    'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="240" height="360" viewBox="0 0 240 360"><rect width="240" height="360" fill="%231a1a2e"/><circle cx="120" cy="150" r="42" fill="%23e94560" opacity="0.35"/><text x="120" y="230" font-family="Arial,sans-serif" font-size="20" fill="%23f5f5f5" text-anchor="middle">NexoHub</text></svg>';

  return (
    <Link
      to={`/peliculas/${movie.id}`}
      className="group relative flex flex-col bg-[#16213e] border border-[#0f3460] rounded-xl overflow-hidden hover:border-[#e94560]/60 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-black/50"
    >
      {/* Poster Image */}
      <div className="relative aspect-[2/3] w-full bg-[#1a1a2e] overflow-hidden">
        <img
          src={movie.posterUrl || fallbackPoster}
          alt={movie.title}
          loading="lazy"
          className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
          onError={(e) => {
            (e.target as HTMLImageElement).src = fallbackPoster;
          }}
        />
        <div className="absolute inset-0 bg-gradient-to-t from-[#16213e] via-transparent to-transparent opacity-80" />

        {/* Favorite Button */}
        <button
          type="button"
          onClick={handleHeartClick}
          className={`absolute top-2.5 right-2.5 p-2 rounded-lg backdrop-blur-md transition-all cursor-pointer ${
            isFav
              ? 'bg-[#e94560] text-white shadow-lg shadow-[#e94560]/40'
              : 'bg-[#1a1a2e]/70 text-[#a0a0a0] hover:text-white hover:bg-[#1a1a2e]'
          }`}
        >
          <Heart className={`h-4 w-4 ${isFav ? 'fill-white' : ''}`} />
        </button>

        {/* Genre Badge */}
        <div className="absolute bottom-2.5 left-2.5">
          <span className="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-[#e94560]/90 text-white shadow-sm">
            {movie.genre.name}
          </span>
        </div>
      </div>

      {/* Info Content */}
      <div className="p-4 flex-1 flex flex-col justify-between gap-3">
        <div>
          <h3 className="font-bold text-sm text-[#f5f5f5] group-hover:text-[#e94560] transition-colors line-clamp-1">
            {movie.title}
          </h3>
          <div className="flex items-center gap-2 mt-1 text-xs text-[#a0a0a0]">
            {movie.releaseYear && (
              <span className="flex items-center gap-1">
                <Calendar className="h-3 w-3 text-[#a0a0a0]" />
                {movie.releaseYear}
              </span>
            )}
            {movie.director && (
              <>
                <span className="text-[#0f3460]">•</span>
                <span className="truncate">{movie.director}</span>
              </>
            )}
          </div>
        </div>

        <div className="flex items-center justify-between pt-2 border-t border-[#0f3460]/80">
          <StarRating value={movie.ratingAvg} readOnly size="sm" showNumber />
          <span className="text-[11px] text-[#a0a0a0] font-medium">
            {movie.reviewsCount} {movie.reviewsCount === 1 ? 'reseña' : 'reseñas'}
          </span>
        </div>
      </div>
    </Link>
  );
}
