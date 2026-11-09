import { useState, useMemo } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Calendar,
  ChevronDown,
  ChevronUp,
  Film,
  Heart,
  MessageSquare,
  Send,
  Star,
  Trash2,
} from 'lucide-react';
import { toast } from 'sonner';
import type { MovieDto, ReviewDto } from '@nexohub/shared';
import { Badge, Button, Combobox, ConfirmDialog, Input, Skeleton, StarRating } from '../components/ui';
import { apiClient } from '../lib/api-client';
import { cn } from '../lib/cn';
import { useAuthStore } from '../stores/auth.store';

type SortOption = 'recent' | 'rating-desc' | 'rating-asc' | 'likes';

export function MovieDetailPage() {
  const { id } = useParams<{ id: string }>();
  const movieId = Number(id);
  const queryClient = useQueryClient();
  const { user } = useAuthStore();

  // Estados para reseña
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [sortBy, setSortBy] = useState<SortOption>('recent');
  const [replyOpenMap, setReplyOpenMap] = useState<Record<number, boolean>>({});
  const [replyTextMap, setReplyTextMap] = useState<Record<number, string>>({});
  const [deletingReviewId, setDeletingReviewId] = useState<number | null>(null);

  // Carga de película
  const { data: movie, isLoading: isMovieLoading } = useQuery({
    queryKey: ['movie', movieId],
    queryFn: async () => {
      const res = await apiClient<{ data: MovieDto }>(`/movies/${movieId}`);
      return res.data;
    },
    enabled: !isNaN(movieId),
  });

  // Estado local para favorito sincronizado
  const [isFavorite, setIsFavorite] = useState<boolean | null>(null);
  const currentIsFav = isFavorite !== null ? isFavorite : Boolean(movie?.isFavorite);

  // Carga de reseñas
  const { data: reviews, isLoading: isReviewsLoading } = useQuery({
    queryKey: ['reviews', movieId],
    queryFn: async () => {
      const res = await apiClient<{ data: ReviewDto[] }>(`/reviews/movie/${movieId}`);
      return res.data;
    },
    enabled: !isNaN(movieId),
  });

  // Mutación para favorito
  const toggleFavoriteMutation = useMutation({
    mutationFn: async () => {
      if (!currentIsFav) {
        return apiClient(`/users/me/favorites/${movieId}`, { method: 'PUT' });
      } else {
        return apiClient(`/users/me/favorites/${movieId}`, { method: 'DELETE' });
      }
    },
    onMutate: () => {
      setIsFavorite(!currentIsFav);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['movie', movieId] });
      queryClient.invalidateQueries({ queryKey: ['favorites'] });
      toast.success(!currentIsFav ? 'Agregada a tus favoritos' : 'Eliminada de tus favoritos');
    },
    onError: () => {
      setIsFavorite(currentIsFav);
      toast.error('No se pudo actualizar favoritos');
    },
  });

  function handleFavoriteToggle() {
    if (!user) {
      toast.error('Debes iniciar sesión para guardar favoritos');
      return;
    }
    toggleFavoriteMutation.mutate();
  }

  // Mutación para publicar reseña
  const submitReviewMutation = useMutation({
    mutationFn: async () => {
      return apiClient(`/reviews/movie/${movieId}`, {
        method: 'POST',
        body: JSON.stringify({ rating, content: comment }),
      });
    },
    onSuccess: () => {
      toast.success('Reseña publicada (+reputación por género)');
      setComment('');
      queryClient.invalidateQueries({ queryKey: ['movie', movieId] });
      queryClient.invalidateQueries({ queryKey: ['reviews', movieId] });
    },
    onError: (err: any) => {
      toast.error(err.message || 'Error al guardar la reseña');
    },
  });

  // Mutación para like en reseña
  const toggleLikeMutation = useMutation({
    mutationFn: async (reviewId: number) => {
      return apiClient<{ data: { hasLiked: boolean; likesCount: number } }>(
        `/reviews/${reviewId}/like`,
        { method: 'POST' },
      );
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reviews', movieId] });
    },
    onError: () => {
      toast.error('No se pudo registrar la reacción');
    },
  });

  // Mutación para responder
  const replyMutation = useMutation({
    mutationFn: async ({ reviewId, content }: { reviewId: number; content: string }) => {
      return apiClient(`/reviews/${reviewId}/responses`, {
        method: 'POST',
        body: JSON.stringify({ content }),
      });
    },
    onSuccess: (_, variables) => {
      toast.success('Respuesta enviada correctamente');
      setReplyTextMap((prev) => ({ ...prev, [variables.reviewId]: '' }));
      queryClient.invalidateQueries({ queryKey: ['reviews', movieId] });
    },
    onError: (err: any) => {
      toast.error(err.message || 'Error al publicar respuesta');
    },
  });

  // Mutación para eliminar reseña
  const deleteReviewMutation = useMutation({
    mutationFn: async (reviewId: number) => {
      return apiClient(`/reviews/${reviewId}`, { method: 'DELETE' });
    },
    onSuccess: () => {
      toast.success('Reseña eliminada');
      queryClient.invalidateQueries({ queryKey: ['movie', movieId] });
      queryClient.invalidateQueries({ queryKey: ['reviews', movieId] });
    },
    onError: () => {
      toast.error('No se pudo eliminar la reseña');
    },
  });

  // Opciones de ordenamiento usando Combobox
  const sortOptions = [
    { value: 'recent', label: 'Más reciente' },
    { value: 'rating-desc', label: 'Mayor calificación' },
    { value: 'rating-asc', label: 'Menor calificación' },
    { value: 'likes', label: 'Más valoradas' },
  ];

  // Ordenamiento de reseñas
  const sortedReviews = useMemo(() => {
    if (!reviews) return [];
    const list = [...reviews];
    switch (sortBy) {
      case 'recent':
        return list.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());
      case 'rating-desc':
        return list.sort((a, b) => b.rating - a.rating);
      case 'rating-asc':
        return list.sort((a, b) => a.rating - b.rating);
      case 'likes':
        return list.sort((a, b) => b.likesCount - a.likesCount);
      default:
        return list;
    }
  }, [reviews, sortBy]);

  const ratingDescriptions: Record<number, string> = {
    1: 'Mala',
    2: 'Regular',
    3: 'Buena',
    4: 'Muy buena',
    5: 'Excelente obra maestra',
  };

  if (isMovieLoading) {
    return (
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start py-6 animate-pulse">
        <div className="lg:col-span-5 space-y-4">
          <Skeleton className="aspect-[2/3] w-full rounded-2xl" />
          <Skeleton className="h-8 w-3/4 rounded-lg" />
          <Skeleton className="h-20 w-full rounded-xl" />
        </div>
        <div className="lg:col-span-7 space-y-4">
          <Skeleton className="h-20 w-full rounded-xl" />
          <Skeleton className="h-40 w-full rounded-xl" />
          <Skeleton className="h-40 w-full rounded-xl" />
        </div>
      </div>
    );
  }

  if (!movie) {
    return (
      <div className="py-20 text-center space-y-4">
        <Film className="h-14 w-14 text-[#a0a0a0] mx-auto opacity-50" />
        <h2 className="text-2xl font-bold text-[#f5f5f5]">Película no encontrada</h2>
        <Link to="/explorar">
          <Button variant="primary">Volver al catálogo</Button>
        </Link>
      </div>
    );
  }

  const fallbackPoster = 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=600&auto=format&fit=crop&q=80';
  const posterUrl = movie.posterUrl || fallbackPoster;
  const reviewsCount = reviews?.length ?? movie.reviewsCount;
  const ratingAvg = movie.ratingAvg > 0 ? movie.ratingAvg : (reviews && reviews.length > 0 ? reviews.reduce((acc, r) => acc + r.rating, 0) / reviews.length : 0);

  return (
    <div className="pb-16 pt-2">
      {/* Vista dividida: Columna izquierda tarjeta fija / Columna derecha interacción */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {/* ========================================================= */}
        {/* COLUMNA IZQUIERDA: Tarjeta de Película con foto e info    */}
        {/* ========================================================= */}
        <aside className="lg:col-span-5 lg:sticky lg:top-20">
          <div className="bg-[#16213e] border border-[#0f3460] rounded-2xl p-6 sm:p-7 shadow-2xl relative overflow-hidden space-y-6">
            {/* Poster con botón de favoritos flotante */}
            <div className="relative aspect-[2/3] w-full rounded-xl overflow-hidden shadow-2xl border border-[#0f3460]/70 bg-[#1a1a2e] group">
              <img
                src={posterUrl}
                alt={movie.title}
                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                onError={(e) => {
                  (e.currentTarget as HTMLImageElement).src = fallbackPoster;
                }}
              />

              {/* Botón Circular Favorito con estrella */}
              <button
                type="button"
                onClick={handleFavoriteToggle}
                title={currentIsFav ? 'Quitar de favoritos' : 'Agregar a favoritos'}
                aria-label="Favorito"
                className={cn(
                  'absolute bottom-3 right-3 h-12 w-12 rounded-full border flex items-center justify-center transition-all duration-200 shadow-xl backdrop-blur-md cursor-pointer',
                  currentIsFav
                    ? 'bg-[#e94560] border-[#e94560] text-white hover:bg-[#d63d56] scale-105 shadow-[#e94560]/40'
                    : 'bg-[#16213e]/85 border-[#0f3460] text-[#cbd5e1] hover:text-amber-400 hover:border-amber-400/60 hover:bg-[#16213e]',
                )}
              >
                <Star
                  className={cn(
                    'h-6 w-6 transition-transform active:scale-125',
                    currentIsFav ? 'fill-white text-white' : 'text-current',
                  )}
                />
              </button>
            </div>

            {/* Badges de Género y Destacada */}
            <div className="space-y-3">
              <div className="flex flex-wrap items-center gap-2">
                <Badge variant="rose" size="md">
                  {movie.genre.name}
                </Badge>
                {movie.featured && (
                  <Badge variant="amber" size="md">
                    Destacada
                  </Badge>
                )}
              </div>

              {/* Título de la Película */}
              <h1 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-tight">
                {movie.title}
              </h1>

              {/* Subtítulo: Año y Dirección */}
              <div className="flex flex-wrap items-center gap-2 text-xs sm:text-sm text-[#a0a0a0]">
                {movie.releaseYear && (
                  <span className="flex items-center gap-1.5 font-medium">
                    <Calendar className="h-4 w-4 text-[#e94560]" />
                    {movie.releaseYear}
                  </span>
                )}
                {movie.director && (
                  <>
                    <span className="text-[#0f3460]">•</span>
                    <span>
                      Dirigida por <strong className="text-[#f5f5f5] font-semibold">{movie.director}</strong>
                    </span>
                  </>
                )}
              </div>
            </div>

            {/* Caja de Calificación de la Comunidad */}
            <div className="p-4 rounded-xl bg-[#0f172a]/70 border border-[#0f3460] flex items-center justify-between gap-4">
              <div>
                <div className="flex items-baseline gap-1.5">
                  <span className="text-3xl font-black text-white">
                    {ratingAvg > 0 ? ratingAvg.toFixed(1) : '—'}
                  </span>
                  <span className="text-xs font-semibold text-[#a0a0a0]">/ 5.0</span>
                </div>
                <p className="text-[11px] text-[#a0a0a0] mt-1 font-medium">
                  {reviewsCount} {reviewsCount === 1 ? 'reseña de la comunidad' : 'reseñas de la comunidad'}
                </p>
              </div>

              <div className="flex items-center gap-1">
                <StarRating value={ratingAvg} readOnly size="md" />
              </div>
            </div>

            {/* Sinopsis */}
            {movie.synopsis && (
              <div className="space-y-2 pt-1 border-t border-[#0f3460]/40">
                <h3 className="text-xs font-bold uppercase tracking-widest text-[#a0a0a0]">SINOPSIS</h3>
                <p className="text-sm text-[#cbd5e1] leading-relaxed whitespace-pre-line">
                  {movie.synopsis}
                </p>
              </div>
            )}
          </div>
        </aside>

        {/* ========================================================= */}
        {/* COLUMNA DERECHA: Reseñas, Interacción y Formulario         */}
        {/* ========================================================= */}
        <section className="lg:col-span-7 space-y-6">
          {/* Header de Reseñas */}
          <div className="flex items-center justify-between">
            <h2 className="text-xs font-bold tracking-widest text-[#a0a0a0] uppercase flex items-center gap-2">
              <MessageSquare className="h-4 w-4 text-[#e94560]" />
              RESEÑAS
            </h2>
          </div>

          {/* Tarjeta de Resumen y Orden usando Combobox Reutilizable */}
          <div className="bg-[#16213e] border border-[#0f3460] rounded-xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 shadow-lg">
            {/* Izquierda: Cantidad de opiniones */}
            <div>
              <span className="text-[11px] font-bold text-[#e94560] uppercase tracking-wider block">
                RESEÑAS
              </span>
              <span className="text-lg sm:text-xl font-bold text-white">
                {reviewsCount} {reviewsCount === 1 ? 'opinión' : 'opiniones'}
              </span>
            </div>

            {/* Centro: Rating y Estrellas */}
            <div className="flex items-center gap-3">
              <span className="text-2xl font-black text-white">
                {ratingAvg > 0 ? ratingAvg.toFixed(1) : '5.0'}
              </span>
              <StarRating value={ratingAvg > 0 ? ratingAvg : 5} readOnly size="md" />
            </div>

            {/* Derecha: Selector de Orden usando Combobox reutilizable */}
            <div className="flex items-center gap-2 min-w-[190px]">
              <span className="text-xs font-bold text-[#a0a0a0] uppercase tracking-wider shrink-0">
                ORDEN
              </span>
              <div className="w-48">
                <Combobox
                  options={sortOptions}
                  value={sortBy}
                  onChange={(val) => setSortBy((val || 'recent') as SortOption)}
                  clearable={false}
                  searchable={false}
                  size="sm"
                />
              </div>
            </div>
          </div>

          {/* Listado de Reseñas */}
          {isReviewsLoading ? (
            <div className="space-y-4">
              <Skeleton className="h-36 w-full rounded-xl" />
              <Skeleton className="h-36 w-full rounded-xl" />
            </div>
          ) : sortedReviews.length === 0 ? (
            <div className="bg-[#16213e] border border-[#0f3460] rounded-xl p-8 text-center space-y-3 shadow-md">
              <MessageSquare className="h-10 w-10 text-[#a0a0a0] mx-auto opacity-40" />
              <h3 className="text-base font-bold text-white">Sin opiniones todavía</h3>
              <p className="text-xs text-[#a0a0a0] max-w-sm mx-auto">
                Sé el primero en compartir tu calificación y análisis de esta película.
              </p>
            </div>
          ) : (
            <div className="space-y-4">
              {sortedReviews.map((review) => {
                const userInitial = (review.user?.name || review.user?.username || 'U')
                  .charAt(0)
                  .toUpperCase();
                const isRepliesOpen = Boolean(replyOpenMap[review.id]);
                const responsesCount = review.responses?.length ?? 0;
                const canDelete =
                  user &&
                  (user.id === review.user?.id || user.role === 'ADMIN' || user.role === 'SUPERADMIN');

                return (
                  <article
                    key={review.id}
                    className="bg-[#16213e] border border-[#0f3460] rounded-xl p-5 shadow-md space-y-4 transition-all hover:border-[#0f3460]/90"
                  >
                    {/* Header de la Reseña: Avatar, Nombre, Estrellas */}
                    <div className="flex items-center justify-between gap-3">
                      <div className="flex items-center gap-3">
                        {review.user?.avatarUrl ? (
                          <img
                            src={review.user.avatarUrl}
                            alt={review.user.name}
                            className="h-10 w-10 rounded-full object-cover border border-[#0f3460]"
                          />
                        ) : (
                          <div className="h-10 w-10 rounded-full bg-[#0f3460] text-white font-bold flex items-center justify-center border border-white/10 text-sm">
                            {userInitial}
                          </div>
                        )}
                        <div>
                          <Link
                            to={`/u/${review.user?.username}`}
                            className="text-sm font-bold text-white hover:text-[#e94560] transition-colors"
                          >
                            {review.user?.name || review.user?.username}
                          </Link>
                          <p className="text-[11px] text-[#a0a0a0]">
                            @{review.user?.username} •{' '}
                            {new Date(review.createdAt).toLocaleDateString('es-AR', {
                              day: 'numeric',
                              month: 'short',
                              year: 'numeric',
                            })}
                          </p>
                        </div>
                      </div>

                      <div className="flex items-center gap-2">
                        <StarRating value={review.rating} readOnly size="sm" />
                        {canDelete && (
                          <button
                            type="button"
                            onClick={() => setDeletingReviewId(review.id)}
                            className="text-[#a0a0a0] hover:text-[#e94560] p-1.5 transition-colors cursor-pointer"
                            title="Eliminar reseña"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        )}
                      </div>
                    </div>

                    {/* Contenido de la reseña */}
                    {review.content && (
                      <p className="text-sm text-[#cbd5e1] leading-relaxed whitespace-pre-line">
                        {review.content}
                      </p>
                    )}

                    {/* Acciones: Me gusta (♥ N) y Ver respuestas */}
                    <div className="flex flex-wrap items-center gap-3 pt-1 border-t border-[#0f3460]/40">
                      {/* Botón Heart Like */}
                      <button
                        type="button"
                        onClick={() => {
                          if (!user) {
                            toast.error('Debes iniciar sesión para reaccionar');
                            return;
                          }
                          toggleLikeMutation.mutate(review.id);
                        }}
                        className={cn(
                          'px-3 py-1.5 rounded-full border text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
                          review.hasLiked
                            ? 'bg-[#e94560]/20 border-[#e94560] text-[#e94560]'
                            : 'bg-[#0f172a]/70 border-[#0f3460] text-[#cbd5e1] hover:border-[#e94560] hover:text-white',
                        )}
                      >
                        <Heart
                          className={cn(
                            'h-3.5 w-3.5',
                            review.hasLiked ? 'fill-[#e94560] text-[#e94560]' : 'text-current',
                          )}
                        />
                        <span>{review.likesCount}</span>
                      </button>

                      {/* Botón Ver Respuestas */}
                      <button
                        type="button"
                        onClick={() =>
                          setReplyOpenMap((prev) => ({ ...prev, [review.id]: !prev[review.id] }))
                        }
                        className="px-3 py-1.5 rounded-full border border-[#0f3460] bg-[#0f172a]/70 hover:border-[#e94560] text-xs font-semibold text-[#cbd5e1] hover:text-white flex items-center gap-1.5 transition-all cursor-pointer"
                      >
                        <span className="uppercase tracking-wider">
                          {isRepliesOpen ? 'Ocultar respuestas' : `Ver respuestas ${responsesCount}`}
                        </span>
                        {isRepliesOpen ? (
                          <ChevronUp className="h-3.5 w-3.5" />
                        ) : (
                          <ChevronDown className="h-3.5 w-3.5" />
                        )}
                      </button>
                    </div>

                    {/* Hilo de Respuestas expandible */}
                    {isRepliesOpen && (
                      <div className="border-l-2 border-[#0f3460] pl-4 space-y-3 mt-3 pt-2">
                        {review.responses && review.responses.length > 0 ? (
                          <div className="space-y-2.5">
                            {review.responses.map((resp) => {
                              const respInitial = (resp.user?.name || resp.user?.username || 'U')
                                .charAt(0)
                                .toUpperCase();
                              return (
                                <div
                                  key={resp.id}
                                  className="bg-[#0f172a]/60 border border-[#0f3460]/80 rounded-lg p-3 space-y-1.5"
                                >
                                  <div className="flex items-center gap-2">
                                    <div className="h-6 w-6 rounded-full bg-[#0f3460] text-white font-bold flex items-center justify-center text-[10px]">
                                      {respInitial}
                                    </div>
                                    <span className="text-xs font-bold text-white">
                                      {resp.user?.name || resp.user?.username}
                                    </span>
                                    <span className="text-[10px] text-[#a0a0a0]">
                                      {new Date(resp.createdAt).toLocaleDateString('es-AR')}
                                    </span>
                                  </div>
                                  <p className="text-xs text-[#cbd5e1] leading-relaxed pl-8">
                                    {resp.content}
                                  </p>
                                </div>
                              );
                            })}
                          </div>
                        ) : (
                          <p className="text-xs text-[#a0a0a0] italic">
                            Aún no hay respuestas a esta reseña.
                          </p>
                        )}

                        {/* Formulario rápido para responder con Input y Button reutilizables */}
                        {user ? (
                          <div className="flex items-center gap-2 pt-2">
                            <div className="flex-1">
                              <Input
                                value={replyTextMap[review.id] || ''}
                                onChange={(e) =>
                                  setReplyTextMap((prev) => ({ ...prev, [review.id]: e.target.value }))
                                }
                                placeholder="Escribe una respuesta..."
                                className="!py-1.5 !text-xs !bg-[#0f172a]"
                                onKeyDown={(e) => {
                                  if (e.key === 'Enter' && !e.shiftKey) {
                                    e.preventDefault();
                                    const text = (replyTextMap[review.id] || '').trim();
                                    if (text) {
                                      replyMutation.mutate({ reviewId: review.id, content: text });
                                    }
                                  }
                                }}
                              />
                            </div>
                            <Button
                              type="button"
                              size="sm"
                              variant="primary"
                              disabled={replyMutation.isPending}
                              isLoading={replyMutation.isPending}
                              onClick={() => {
                                const text = (replyTextMap[review.id] || '').trim();
                                if (!text) {
                                  toast.error('Escribe una respuesta');
                                  return;
                                }
                                replyMutation.mutate({ reviewId: review.id, content: text });
                              }}
                              leftIcon={<Send className="h-3.5 w-3.5" />}
                            >
                              Responder
                            </Button>
                          </div>
                        ) : (
                          <p className="text-xs text-[#a0a0a0] pt-1">
                            <Link to="/login" className="text-[#e94560] hover:underline font-semibold">
                              Inicia sesión
                            </Link>{' '}
                            para responder a esta reseña.
                          </p>
                        )}
                      </div>
                    )}
                  </article>
                );
              })}
            </div>
          )}

          {/* ========================================================= */}
          {/* SECCIÓN ESCRIBIR RESEÑA (Matching Foto 3)                 */}
          {/* ========================================================= */}
          <div className="bg-[#16213e] border border-[#0f3460] rounded-xl p-6 shadow-xl space-y-4">
            <div>
              <h3 className="text-lg font-bold text-white tracking-tight">Escribir reseña</h3>
              <p className="text-xs text-[#a0a0a0] mt-0.5">
                Tu calificación ayuda al resto a decidir.
              </p>
            </div>

            {user ? (
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  if (rating < 1) {
                    toast.error('Debes seleccionar al menos 1 estrella');
                    return;
                  }
                  submitReviewMutation.mutate();
                }}
                className="space-y-4"
              >
                {/* Selector de Calificación con Estrellas */}
                <div className="space-y-1.5">
                  <label className="block text-xs font-bold text-[#a0a0a0] uppercase tracking-wider">
                    CALIFICACIÓN
                  </label>
                  <div className="flex items-center gap-3">
                    <StarRating value={rating} onChange={setRating} size="lg" />
                    <span className="text-xs font-semibold text-[#cbd5e1]">
                      {ratingDescriptions[rating] || ''}
                    </span>
                  </div>
                </div>

                {/* Comentario de la Reseña */}
                <div className="space-y-1.5">
                  <label className="block text-xs font-bold text-[#a0a0a0] uppercase tracking-wider">
                    RESEÑA
                  </label>
                  <textarea
                    value={comment}
                    onChange={(e) => setComment(e.target.value)}
                    placeholder="Escribe tu opinión detallada sobre la trama, dirección, actuaciones o fotografía..."
                    rows={4}
                    className="w-full bg-[#0f172a] border border-[#0f3460] focus:border-[#e94560] rounded-xl p-3.5 text-sm text-white placeholder-[#64748b] outline-none transition-colors resize-y min-h-[110px]"
                  />
                </div>

                {/* Botón de Publicar Reseña con componente Button reutilizable */}
                <Button
                  type="submit"
                  variant="primary"
                  size="lg"
                  className="w-full"
                  isLoading={submitReviewMutation.isPending}
                  leftIcon={<Send className="h-4 w-4" />}
                >
                  {submitReviewMutation.isPending
                    ? 'Publicando...'
                    : movie.userReview
                      ? 'Actualizar reseña'
                      : 'Publicar reseña'}
                </Button>
              </form>
            ) : (
              <div className="bg-[#0f172a]/60 border border-[#0f3460] rounded-xl p-5 text-center space-y-3">
                <p className="text-sm text-[#cbd5e1]">
                  Debes tener una cuenta en NexoHub para calificar y dejar tu reseña en esta película.
                </p>
                <div className="flex justify-center gap-3 pt-1">
                  <Link to="/login">
                    <Button variant="primary" size="sm">
                      Iniciar Sesión
                    </Button>
                  </Link>
                  <Link to="/registro">
                    <Button variant="outline" size="sm">
                      Crear Cuenta
                    </Button>
                  </Link>
                </div>
              </div>
            )}
          </div>
        </section>
      </div>

      {/* Modal de confirmación para eliminar reseña */}
      <ConfirmDialog
        open={deletingReviewId !== null}
        onClose={() => setDeletingReviewId(null)}
        onConfirm={() => {
          if (deletingReviewId !== null) {
            deleteReviewMutation.mutate(deletingReviewId, {
              onSettled: () => setDeletingReviewId(null),
            });
          }
        }}
        title="¿Eliminar esta reseña?"
        description="Esta acción eliminará tu calificación, tu crítica y todas las respuestas asociadas de forma permanente."
        confirmText="Eliminar reseña"
        cancelText="Cancelar"
        variant="danger"
        isLoading={deleteReviewMutation.isPending}
      />
    </div>
  );
}
