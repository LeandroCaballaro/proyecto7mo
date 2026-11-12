import { useRef } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import {
  ArrowRight,
  Award,
  ChevronLeft,
  ChevronRight,
  Film,
  Play,
  ShieldCheck,
  Sparkles,
  Star,
  TrendingUp,
  Users,
  Zap,
} from 'lucide-react';
import { GENRES, type MovieDto } from '@nexohub/shared';
import { MovieCard } from '../features/movies/MovieCard';
import { apiClient } from '../lib/api-client';

interface HomeGenre {
  id?: number;
  name: string;
  slug: string;
  moviesCount?: number;
  featuredMovie?: { id: number; title: string; posterUrl: string | null } | null;
}

function RailButton({ direction, onClick }: { direction: 'left' | 'right'; onClick: () => void }) {
  const Icon = direction === 'left' ? ChevronLeft : ChevronRight;
  return (
    <button
      type="button"
      onClick={onClick}
      aria-label={
        direction === 'left' ? 'Desplazar hacia la izquierda' : 'Desplazar hacia la derecha'
      }
      className="flex h-9 w-9 items-center justify-center rounded-full border border-[#0f3460] bg-[#16213e] text-[#a0a0a0] transition-all hover:border-[#e94560]/60 hover:text-white active:scale-95"
    >
      <Icon className="h-4 w-4" />
    </button>
  );
}

export function HomePage() {
  const moviesRailRef = useRef<HTMLDivElement>(null);
  const genresRailRef = useRef<HTMLDivElement>(null);

  const { data: featuredMovies, isLoading } = useQuery({
    queryKey: ['movies', 'featured'],
    queryFn: async () => (await apiClient<{ data: MovieDto[] }>('/movies/featured?limit=10')).data,
  });

  const { data: stats } = useQuery({
    queryKey: ['stats', 'summary'],
    queryFn: async () =>
      (
        await apiClient<{
          data: {
            usersCount: number;
            reviewsCount: number;
            moviesCount: number;
            genresCount: number;
          };
        }>('/stats/summary')
      ).data,
  });

  const { data: genresData } = useQuery({
    queryKey: ['movies', 'genres'],
    queryFn: async () => (await apiClient<{ data: HomeGenre[] }>('/movies/genres')).data,
  });

  const genres: readonly HomeGenre[] = genresData?.length ? genresData : GENRES;
  function scrollRail(ref: React.RefObject<HTMLDivElement | null>, direction: number) {
    ref.current?.scrollBy({
      left: direction * Math.min(ref.current.clientWidth * 0.8, 760),
      behavior: 'smooth',
    });
  }

  return (
    <div className="-mt-6 overflow-hidden">
      <section className="relative isolate min-h-[590px] overflow-hidden border-b border-[#0f3460]">
        <div className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_78%_35%,rgba(233,69,96,0.12),transparent_34%)]" />
        <div className="absolute inset-0 -z-10 bg-gradient-to-br from-[#1a1a2e] via-[#181b32] to-[#121a31]" />

        <div className="mx-auto grid min-h-[590px] max-w-7xl items-center px-4 py-16 sm:px-6 lg:grid-cols-12 lg:py-20">
          <div className="max-w-3xl lg:col-span-7">
            <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-[#e94560]/35 bg-[#e94560]/10 px-3.5 py-1.5 text-xs font-bold text-[#ff6b81] backdrop-blur-sm">
              <Sparkles className="h-3.5 w-3.5" />
              Tu próxima gran película empieza acá
            </div>
            <h1 className="text-4xl font-black leading-[1.04] tracking-[-0.04em] text-white sm:text-6xl lg:text-7xl">
              El cine se disfruta más cuando la recomendación{' '}
              <span className="text-[#e94560]">tiene criterio.</span>
            </h1>
            <p className="mt-6 max-w-2xl text-base leading-7 text-[#b8b8c5] sm:text-lg">
              Descubrí películas a través de reseñas reales, encontrá especialistas por género y
              construí tu propia voz dentro de la comunidad.
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <Link
                to="/explorar"
                className="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-[#e94560] px-6 font-bold text-white shadow-xl shadow-[#e94560]/20 transition-all hover:-translate-y-0.5 hover:bg-[#f0526b]"
              >
                <Play className="h-4 w-4 fill-current" /> Explorar catálogo
              </Link>
              <a
                href="#destacadas"
                className="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 font-bold text-white backdrop-blur-sm transition-all hover:bg-white/10"
              >
                Ver recomendaciones <ArrowRight className="h-4 w-4" />
              </a>
            </div>

            <div className="mt-10 grid max-w-xl grid-cols-3 gap-3 border-t border-white/10 pt-6">
              {[
                { icon: Film, value: stats?.moviesCount, label: 'Películas' },
                { icon: Star, value: stats?.reviewsCount, label: 'Reseñas' },
                { icon: Users, value: stats?.usersCount, label: 'Cinéfilos' },
              ].map(({ icon: Icon, value, label }) => (
                <div key={label} className="min-w-0">
                  <div className="flex items-center gap-2">
                    <Icon className="h-4 w-4 shrink-0 text-[#e94560]" />
                    <span className="text-xl font-black tabular-nums text-white">
                      {value ?? '—'}
                    </span>
                  </div>
                  <span className="mt-1 block text-[11px] text-[#8f8f9f]">{label}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="relative mt-12 flex items-center justify-center lg:col-span-5 lg:mt-0">
            <div className="pointer-events-none absolute h-72 w-72 rounded-full bg-[#e94560]/15 blur-3xl sm:h-96 sm:w-96" />
            <div className="pointer-events-none absolute h-64 w-64 rounded-full border border-[#e94560]/10 sm:h-80 sm:w-80" />
            <img
              src="/nexohub-logo.png"
              alt="NexoHub"
              className="nexologo relative z-10 max-h-[320px] object-contain"
            />
          </div>
        </div>
      </section>

      <section id="destacadas" className="scroll-mt-24 py-16 sm:py-20">
        <div className="mx-auto max-w-7xl px-4 sm:px-6">
          <div className="mb-7 flex items-end justify-between gap-4">
            <div>
              <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#e94560]">
                Selección NexoHub
              </span>
              <h2 className="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                Películas para descubrir
              </h2>
              <p className="mt-2 text-sm text-[#a0a0a0]">
                Las mejor valoradas por la comunidad, en un solo recorrido.
              </p>
            </div>
            <div className="hidden gap-2 sm:flex">
              <RailButton direction="left" onClick={() => scrollRail(moviesRailRef, -1)} />
              <RailButton direction="right" onClick={() => scrollRail(moviesRailRef, 1)} />
            </div>
          </div>

          <div
            ref={moviesRailRef}
            className="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-5 scrollbar-none sm:gap-5"
          >
            {isLoading
              ? Array.from({ length: 6 }).map((_, index) => (
                  <div
                    key={index}
                    className="h-[410px] w-[220px] shrink-0 animate-pulse rounded-xl bg-[#16213e]"
                  />
                ))
              : featuredMovies?.map((movie) => (
                  <div
                    key={movie.id}
                    className="w-[72vw] max-w-[235px] shrink-0 snap-start sm:w-[220px]"
                  >
                    <MovieCard movie={movie} />
                  </div>
                ))}
          </div>
          <div className="flex justify-end">
            <Link
              to="/explorar?sort=rating"
              className="inline-flex items-center gap-2 text-sm font-bold text-[#e94560] hover:text-[#ff7187]"
            >
              Ver catálogo completo <ArrowRight className="h-4 w-4" />
            </Link>
          </div>
        </div>
      </section>

      <section
        id="generos"
        className="scroll-mt-24 border-y border-[#0f3460] bg-[#121a31] py-16 sm:py-20"
      >
        <div className="mx-auto max-w-7xl px-4 sm:px-6">
          <div className="mb-7 flex items-end justify-between gap-4">
            <div>
              <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#e94560]">
                Encontrá tu estilo
              </span>
              <h2 className="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                Explorá por género
              </h2>
              <p className="mt-2 text-sm text-[#a0a0a0]">
                Deslizá y entrá directamente al universo que tengas ganas de ver.
              </p>
            </div>
            <div className="hidden gap-2 sm:flex">
              <RailButton direction="left" onClick={() => scrollRail(genresRailRef, -1)} />
              <RailButton direction="right" onClick={() => scrollRail(genresRailRef, 1)} />
            </div>
          </div>

          <div
            ref={genresRailRef}
            className="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-5 scrollbar-none"
          >
            {genres.map((genre) => (
              <Link
                key={genre.slug}
                to={`/explorar?genre=${genre.slug}`}
                className="group relative h-52 w-[78vw] max-w-[310px] shrink-0 snap-start overflow-hidden rounded-2xl border border-[#0f3460] bg-[#16213e] p-5 transition-all hover:-translate-y-1 hover:border-[#e94560]/50"
              >
                {genre.featuredMovie?.posterUrl && (
                  <img
                    src={genre.featuredMovie.posterUrl}
                    alt=""
                    loading="lazy"
                    className="absolute inset-0 h-full w-full object-cover opacity-40 transition-transform duration-700 group-hover:scale-105 group-hover:opacity-50"
                  />
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-[#10182d] via-[#10182d]/65 to-transparent" />
                <div className="relative flex h-full flex-col justify-end">
                  <div className="mb-auto flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-black/25 text-[#ff6b81] backdrop-blur-md">
                    <Film className="h-4 w-4" />
                  </div>
                  <h3 className="text-xl font-black text-white">{genre.name}</h3>
                  <p className="mt-1 text-xs text-white/60">
                    {genre.moviesCount ?? 0} películas disponibles
                  </p>
                </div>
              </Link>
            ))}
          </div>
          <Link
            to="/generos"
            className="inline-flex items-center gap-2 text-sm font-bold text-[#e94560] hover:text-[#ff7187]"
          >
            Ver todos los géneros <ArrowRight className="h-4 w-4" />
          </Link>
        </div>
      </section>

      <section id="por-que-nexohub" className="scroll-mt-24 py-16 sm:py-20">
        <div className="mx-auto max-w-7xl px-4 sm:px-6">
          <div className="mx-auto mb-10 max-w-2xl text-center">
            <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#e94560]">
              Una comunidad con criterio
            </span>
            <h2 className="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">
              No es otra lista de películas.
            </h2>
            <p className="mt-4 text-sm leading-6 text-[#a0a0a0]">
              NexoHub conecta cada recomendación con la experiencia real de quien la escribe.
            </p>
          </div>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {[
              {
                icon: ShieldCheck,
                title: 'Reputación por género',
                text: 'Identificá rápidamente quién realmente conoce el tipo de cine que estás buscando.',
              },
              {
                icon: Users,
                title: 'Comunidad activa',
                text: 'Encontrá otros cinéfilos y descubrí películas a partir de gustos compartidos.',
              },
              {
                icon: Zap,
                title: 'Descubrimiento preciso',
                text: 'Filtrá por género, valoración y conversación para llegar antes a algo que valga la pena.',
              },
              {
                icon: Award,
                title: 'Críticas que cuentan',
                text: 'Cada reacción de la comunidad ayuda a destacar las opiniones más útiles.',
              },
            ].map(({ icon: Icon, title, text }) => (
              <article
                key={title}
                className="rounded-2xl border border-[#0f3460] bg-[#16213e]/70 p-6 text-center transition-all hover:-translate-y-1 hover:border-[#e94560]/40"
              >
                <div className="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-[#e94560]/10 text-[#e94560]">
                  <Icon className="h-5 w-5" />
                </div>
                <h3 className="mt-6 text-lg font-bold text-white">{title}</h3>
                <p className="mt-2 text-xs leading-5 text-[#a0a0a0]">{text}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section id="comunidad" className="scroll-mt-24 px-4 pb-10 sm:px-6 sm:pb-16">
        <div className="relative mx-auto max-w-7xl overflow-hidden rounded-3xl border border-[#e94560]/30 bg-[#e94560] px-6 py-12 text-center shadow-2xl shadow-[#e94560]/10 sm:px-10 sm:py-16">
          <div className="absolute -left-20 -top-24 h-64 w-64 rounded-full border-[45px] border-white/5" />
          <div className="absolute -bottom-32 -right-16 h-72 w-72 rounded-full border-[50px] border-white/5" />
          <div className="relative mx-auto max-w-2xl">
            <TrendingUp className="mx-auto h-7 w-7 text-white/80" />
            <h2 className="mt-4 text-3xl font-black text-white sm:text-4xl">
              Tu opinión también construye el catálogo
            </h2>
            <p className="mt-4 text-sm leading-6 text-white/85 sm:text-base">
              Compartí reseñas, ganá reputación en tus géneros favoritos y ayudá a otros a elegir
              mejor.
            </p>
            <div className="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
              <Link
                to="/registro"
                className="inline-flex h-12 items-center justify-center rounded-xl bg-white px-6 font-bold text-[#d93651] transition-transform hover:-translate-y-0.5"
              >
                Crear mi perfil
              </Link>
              <Link
                to="/explorar"
                className="inline-flex h-12 items-center justify-center rounded-xl border border-white/50 px-6 font-bold text-white transition-colors hover:bg-white/10"
              >
                Seguir explorando
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
