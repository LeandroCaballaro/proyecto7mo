import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
  ArrowUpRight,
  Calendar,
  ChevronLeft,
  ChevronRight,
  Flame,
  MessageCircle,
  Sparkles,
  Star,
  TrendingUp,
} from 'lucide-react';
import type { ApiPaginated, MovieDto } from '@nexohub/shared';
import { Skeleton } from '../components/ui';
import { apiClient } from '../lib/api-client';

type RecommendationMode = 'featured' | 'rating' | 'reviews' | 'newest';

const modes: Array<{
  value: RecommendationMode;
  label: string;
  icon: typeof Sparkles;
}> = [
  { value: 'featured', label: 'Para vos', icon: Sparkles },
  { value: 'rating', label: 'Mejor valoradas', icon: Star },
  { value: 'reviews', label: 'Más comentadas', icon: MessageCircle },
  { value: 'newest', label: 'Recién agregadas', icon: Flame },
];

export function GenresPage() {
  const [mode, setMode] = useState<RecommendationMode>('featured');
  const [activeIndex, setActiveIndex] = useState(0);

  const { data: recommendations, isLoading } = useQuery({
    queryKey: ['movies', 'recommendations', mode],
    queryFn: async () => {
      if (mode === 'featured') {
        return (await apiClient<{ data: MovieDto[] }>('/movies/featured?limit=12')).data;
      }
      return (
        await apiClient<ApiPaginated<MovieDto>>(
          `/movies?page=1&pageSize=12&sort=${mode === 'newest' ? 'newest' : mode}`,
        )
      ).data;
    },
  });

  const movies = recommendations ?? [];
  const activeMovie = movies[activeIndex % Math.max(movies.length, 1)];

  useEffect(() => {
    setActiveIndex(0);
  }, [mode]);

  useEffect(() => {
    if (movies.length < 2) return;
    const interval = window.setInterval(() => {
      setActiveIndex((current) => (current + 1) % movies.length);
    }, 7000);
    return () => window.clearInterval(interval);
  }, [mode, movies.length]);

  function changeMovie(direction: number) {
    if (movies.length < 2) return;
    setActiveIndex((current) => (current + direction + movies.length) % movies.length);
  }

  function selectMovie(movie: MovieDto) {
    const index = movies.findIndex((candidate) => candidate.id === movie.id);
    if (index >= 0) setActiveIndex(index);
  }

  const upcomingMovies = movies.filter((movie) => movie.id !== activeMovie?.id).slice(0, 5);

  return (
    <div className="-mt-6 bg-[#10182d]">
      {isLoading || !activeMovie ? (
        <Skeleton className="h-[calc(100vh-4rem)] min-h-[720px] w-full rounded-none" />
      ) : (
        <section className="relative isolate flex min-h-[calc(100vh-4rem)] flex-col overflow-hidden">
          {activeMovie.posterUrl && (
            <img
              key={`backdrop-${activeMovie.id}`}
              src={activeMovie.posterUrl}
              alt=""
              className="absolute inset-0 -z-30 h-full w-full scale-105 animate-in fade-in duration-700 object-cover object-[center_25%] opacity-45 blur-[2px]"
            />
          )}
          <div className="absolute inset-0 -z-20 bg-gradient-to-r from-[#0b1224] via-[#0b1224]/90 to-[#0b1224]/40" />
          <div className="absolute inset-0 -z-20 bg-gradient-to-t from-[#0b1224] via-[#0b1224]/20 to-[#0b1224]/75" />
          <div className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_78%_42%,rgba(233,69,96,0.18),transparent_28%)]" />

          <div className="mx-auto w-full max-w-7xl px-4 pt-7 sm:px-6">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-[#ff617a]">
                  <TrendingUp className="h-4 w-4" />
                  Recomendaciones NexoHub
                </div>
                <p className="mt-1 text-xs text-white/45">
                  Una selección para encontrar tu próxima película.
                </p>
              </div>

              <nav
                className="flex snap-x gap-2 overflow-x-auto pb-1 scrollbar-none"
                aria-label="Tipo de recomendación"
              >
                {modes.map(({ value, label, icon: Icon }) => (
                  <button
                    key={value}
                    type="button"
                    onClick={() => setMode(value)}
                    className={`flex shrink-0 snap-start items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-bold backdrop-blur-md transition-all ${
                      mode === value
                        ? 'border-[#e94560] bg-[#e94560] text-white shadow-lg shadow-[#e94560]/20'
                        : 'border-white/15 bg-black/20 text-white/65 hover:border-white/30 hover:text-white'
                    }`}
                  >
                    <Icon className="h-3.5 w-3.5" />
                    {label}
                  </button>
                ))}
              </nav>
            </div>
          </div>

          <div className="mx-auto grid w-full max-w-7xl flex-1 items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-12 lg:py-10">
            <div
              key={`content-${activeMovie.id}`}
              className="animate-in fade-in slide-in-from-left-3 duration-500 lg:col-span-7"
              aria-live="polite"
            >
              <div className="flex flex-wrap items-center gap-2 text-xs font-bold">
                <span className="rounded-md bg-[#e94560] px-2.5 py-1 uppercase tracking-wider text-white">
                  {activeMovie.genre.name}
                </span>
                {activeMovie.releaseYear && (
                  <span className="flex items-center gap-1.5 text-white/55">
                    <Calendar className="h-3.5 w-3.5" /> {activeMovie.releaseYear}
                  </span>
                )}
                <span className="flex items-center gap-1.5 text-amber-300">
                  <Star className="h-3.5 w-3.5 fill-current" />
                  {activeMovie.ratingAvg > 0 ? activeMovie.ratingAvg.toFixed(1) : 'Nueva'}
                </span>
                <span className="text-white/40">
                  {activeMovie.reviewsCount} {activeMovie.reviewsCount === 1 ? 'reseña' : 'reseñas'}
                </span>
              </div>

              <h1 className="mt-5 max-w-3xl text-5xl font-black leading-[0.95] tracking-[-0.045em] text-white drop-shadow-xl sm:text-6xl lg:text-7xl">
                {activeMovie.title}
              </h1>
              <p className="mt-5 max-w-2xl text-sm leading-7 text-white/65 sm:text-base">
                {activeMovie.synopsis ||
                  `Una recomendación destacada de ${activeMovie.genre.name.toLowerCase()} elegida para descubrir algo diferente.`}
              </p>

              <div className="mt-7 flex flex-wrap gap-3">
                <Link
                  to={`/peliculas/${activeMovie.id}`}
                  className="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-white px-6 text-sm font-black text-[#10182d] shadow-xl transition-all hover:-translate-y-0.5 hover:bg-white/90"
                >
                  Ver película <ArrowUpRight className="h-4 w-4" />
                </Link>
                <Link
                  to={`/explorar?genre=${activeMovie.genre.slug}`}
                  className="inline-flex h-12 items-center justify-center rounded-xl border border-white/20 bg-black/25 px-6 text-sm font-bold text-white backdrop-blur-md transition-colors hover:bg-black/40"
                >
                  Más de {activeMovie.genre.name}
                </Link>
              </div>

              {movies.length > 1 && (
                <div className="mt-8 flex items-center gap-4">
                  <button
                    type="button"
                    onClick={() => changeMovie(-1)}
                    aria-label="Recomendación anterior"
                    className="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-black/20 text-white backdrop-blur-md transition-colors hover:bg-white hover:text-[#10182d]"
                  >
                    <ChevronLeft className="h-5 w-5" />
                  </button>
                  <div className="flex items-center gap-1.5">
                    {movies.slice(0, 8).map((movie, index) => (
                      <button
                        key={movie.id}
                        type="button"
                        onClick={() => setActiveIndex(index)}
                        aria-label={`Mostrar ${movie.title}`}
                        className={`h-1 rounded-full transition-all ${index === activeIndex ? 'w-9 bg-[#e94560]' : 'w-4 bg-white/30 hover:bg-white/55'}`}
                      />
                    ))}
                  </div>
                  <button
                    type="button"
                    onClick={() => changeMovie(1)}
                    aria-label="Siguiente recomendación"
                    className="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-black/20 text-white backdrop-blur-md transition-colors hover:bg-white hover:text-[#10182d]"
                  >
                    <ChevronRight className="h-5 w-5" />
                  </button>
                </div>
              )}
            </div>

            <div className="hidden items-center justify-center lg:col-span-5 lg:flex">
              {activeMovie.posterUrl && (
                <div className="relative">
                  <div className="absolute inset-6 -z-10 rounded-[2rem] bg-[#e94560]/30 blur-3xl" />
                  <img
                    key={`poster-${activeMovie.id}`}
                    src={activeMovie.posterUrl}
                    alt={`Póster de ${activeMovie.title}`}
                    className="max-h-[430px] w-auto animate-in fade-in zoom-in-95 rounded-2xl border border-white/15 object-cover shadow-2xl shadow-black/60 duration-500"
                  />
                </div>
              )}
            </div>
          </div>

          {upcomingMovies.length > 0 && (
            <div className="border-t border-white/10 bg-[#080e1d]/55 backdrop-blur-xl">
              <div className="mx-auto max-w-7xl px-4 py-4 sm:px-6">
                <div className="mb-3 flex items-center justify-between">
                  <p className="text-[11px] font-bold uppercase tracking-[0.18em] text-white/45">
                    Próximas recomendaciones
                  </p>
                  <span className="text-[11px] text-white/30">Cambio automático en 7 segundos</span>
                </div>
                <div className="flex snap-x gap-3 overflow-x-auto scrollbar-none">
                  {upcomingMovies.map((movie) => (
                    <button
                      key={movie.id}
                      type="button"
                      onClick={() => selectMovie(movie)}
                      className="group relative h-20 w-48 shrink-0 snap-start overflow-hidden rounded-xl border border-white/10 bg-[#16213e] text-left transition-all hover:-translate-y-0.5 hover:border-[#e94560]/60 sm:w-56"
                    >
                      {movie.posterUrl && (
                        <img
                          src={movie.posterUrl}
                          alt=""
                          loading="lazy"
                          className="absolute inset-0 h-full w-full object-cover opacity-40 transition-transform duration-500 group-hover:scale-105 group-hover:opacity-55"
                        />
                      )}
                      <div className="absolute inset-0 bg-gradient-to-r from-[#0b1224] via-[#0b1224]/80 to-transparent" />
                      <div className="relative flex h-full flex-col justify-end p-3">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-[#ff7187]">
                          {movie.genre.name}
                        </span>
                        <span className="mt-0.5 truncate text-xs font-bold text-white">
                          {movie.title}
                        </span>
                      </div>
                    </button>
                  ))}
                </div>
              </div>
            </div>
          )}
        </section>
      )}
    </div>
  );
}
