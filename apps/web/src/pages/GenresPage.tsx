import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
  ArrowUpRight,
  Clapperboard,
  Compass,
  Drama,
  Film,
  Ghost,
  Heart,
  Laugh,
  Rocket,
  Search,
  Sparkles,
  Swords,
  WandSparkles,
  Zap,
  type LucideIcon,
} from 'lucide-react';
import { GENRES } from '@nexohub/shared';
import { Skeleton } from '../components/ui';
import { apiClient } from '../lib/api-client';

interface GenreWithCount {
  id: number;
  name: string;
  slug: string;
  moviesCount: number;
  featuredMovie?: {
    id: number;
    title: string;
    posterUrl: string | null;
  } | null;
}

interface GenreVisual {
  icon: LucideIcon;
  accent: string;
  glow: string;
  iconBackground: string;
}

const genreVisuals: Record<string, GenreVisual> = {
  accion: {
    icon: Swords,
    accent: 'text-orange-300',
    glow: 'from-orange-500/20 via-orange-500/5',
    iconBackground: 'bg-orange-400/10 border-orange-300/20',
  },
  animacion: {
    icon: Sparkles,
    accent: 'text-violet-300',
    glow: 'from-violet-500/20 via-violet-500/5',
    iconBackground: 'bg-violet-400/10 border-violet-300/20',
  },
  aventura: {
    icon: Compass,
    accent: 'text-emerald-300',
    glow: 'from-emerald-500/20 via-emerald-500/5',
    iconBackground: 'bg-emerald-400/10 border-emerald-300/20',
  },
  'ciencia-ficcion': {
    icon: Rocket,
    accent: 'text-cyan-300',
    glow: 'from-cyan-500/20 via-cyan-500/5',
    iconBackground: 'bg-cyan-400/10 border-cyan-300/20',
  },
  comedia: {
    icon: Laugh,
    accent: 'text-yellow-300',
    glow: 'from-yellow-500/20 via-yellow-500/5',
    iconBackground: 'bg-yellow-400/10 border-yellow-300/20',
  },
  crimen: {
    icon: Search,
    accent: 'text-slate-300',
    glow: 'from-slate-400/20 via-slate-400/5',
    iconBackground: 'bg-slate-300/10 border-slate-200/20',
  },
  documental: {
    icon: Clapperboard,
    accent: 'text-blue-300',
    glow: 'from-blue-500/20 via-blue-500/5',
    iconBackground: 'bg-blue-400/10 border-blue-300/20',
  },
  drama: {
    icon: Drama,
    accent: 'text-rose-300',
    glow: 'from-rose-500/20 via-rose-500/5',
    iconBackground: 'bg-rose-400/10 border-rose-300/20',
  },
  fantasia: {
    icon: WandSparkles,
    accent: 'text-fuchsia-300',
    glow: 'from-fuchsia-500/20 via-fuchsia-500/5',
    iconBackground: 'bg-fuchsia-400/10 border-fuchsia-300/20',
  },
  misterio: {
    icon: Search,
    accent: 'text-indigo-300',
    glow: 'from-indigo-500/20 via-indigo-500/5',
    iconBackground: 'bg-indigo-400/10 border-indigo-300/20',
  },
  romance: {
    icon: Heart,
    accent: 'text-pink-300',
    glow: 'from-pink-500/20 via-pink-500/5',
    iconBackground: 'bg-pink-400/10 border-pink-300/20',
  },
  terror: {
    icon: Ghost,
    accent: 'text-red-300',
    glow: 'from-red-500/20 via-red-500/5',
    iconBackground: 'bg-red-400/10 border-red-300/20',
  },
  thriller: {
    icon: Zap,
    accent: 'text-amber-300',
    glow: 'from-amber-500/20 via-amber-500/5',
    iconBackground: 'bg-amber-400/10 border-amber-300/20',
  },
};

const defaultVisual: GenreVisual = {
  icon: Film,
  accent: 'text-[#e94560]',
  glow: 'from-[#e94560]/20 via-[#e94560]/5',
  iconBackground: 'bg-[#e94560]/10 border-[#e94560]/20',
};

export function GenresPage() {
  const [selectedSlug, setSelectedSlug] = useState('accion');
  const { data: genres, isLoading } = useQuery({
    queryKey: ['movies', 'genres'],
    queryFn: async () => {
      const res = await apiClient<{ data: GenreWithCount[] }>('/movies/genres');
      return res.data;
    },
  });

  const list: GenreWithCount[] =
    genres && genres.length > 0
      ? genres
      : GENRES.map((g) => ({
          id: 0,
          name: g.name,
          slug: g.slug,
          moviesCount: 0,
          featuredMovie: null,
        }));
  const totalMovies = list.reduce((total, genre) => total + (genre.moviesCount ?? 0), 0);
  const selectedGenre = list.find((genre) => genre.slug === selectedSlug) ?? list[0];
  const selectedVisual = selectedGenre
    ? (genreVisuals[selectedGenre.slug] ?? defaultVisual)
    : defaultVisual;
  const SelectedIcon = selectedVisual.icon;

  return (
    <div className="space-y-8 pb-16">
      <header className="mx-auto max-w-3xl text-center">
        <div className="mb-3 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.18em] text-[#e94560]">
          <Film className="h-4 w-4" />
          Explora el catálogo
        </div>
        <h1 className="text-3xl font-black tracking-tight text-[#f5f5f5] sm:text-5xl">
          ¿Qué tenés ganas de ver hoy?
        </h1>
        <p className="mx-auto mt-3 max-w-xl text-sm leading-6 text-[#a0a0a0]">
          Elegí un género y dejá que la comunidad te acerque una buena historia.
        </p>
        <p className="mt-3 text-xs text-[#707382]">
          {list.length} géneros · {totalMovies} películas
        </p>
      </header>

      {isLoading || !selectedGenre ? (
        <Skeleton className="h-[590px] w-full rounded-3xl" />
      ) : (
        <div className="overflow-hidden rounded-3xl border border-[#0f3460] bg-[#121a31] shadow-2xl shadow-black/20 lg:grid lg:min-h-[590px] lg:grid-cols-[280px_1fr]">
          <div className="border-b border-[#0f3460] bg-[#10182d] lg:border-b-0 lg:border-r">
            <div className="hidden border-b border-[#0f3460]/70 px-5 py-5 lg:block">
              <p className="text-[11px] font-bold uppercase tracking-[0.18em] text-[#737686]">
                Todos los géneros
              </p>
            </div>

            <nav
              className="flex gap-1 overflow-x-auto p-3 scrollbar-none lg:max-h-[525px] lg:flex-col lg:overflow-y-auto lg:p-2 lg:pr-1 lg:custom-scrollbar"
              aria-label="Seleccionar género"
            >
              {list.map((genre) => {
                const active = genre.slug === selectedGenre.slug;
                const visual = genreVisuals[genre.slug] ?? defaultVisual;
                const GenreIcon = visual.icon;
                return (
                  <button
                    key={genre.slug}
                    type="button"
                    onClick={() => setSelectedSlug(genre.slug)}
                    className={`group flex shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-all lg:w-full ${
                      active
                        ? 'bg-[#e94560] text-white shadow-lg shadow-[#e94560]/15'
                        : 'text-[#a4a7b4] hover:bg-white/5 hover:text-white'
                    }`}
                  >
                    <GenreIcon className="h-4 w-4 shrink-0" strokeWidth={1.8} />
                    <span className="text-sm font-bold">{genre.name}</span>
                    <span
                      className={`ml-auto hidden text-[11px] tabular-nums lg:block ${active ? 'text-white/70' : 'text-[#656978]'}`}
                    >
                      {genre.moviesCount}
                    </span>
                  </button>
                );
              })}
            </nav>
          </div>

          <section className="relative isolate flex min-h-[480px] items-end overflow-hidden p-6 sm:p-10 lg:min-h-full lg:p-14">
            {selectedGenre.featuredMovie?.posterUrl && (
              <img
                key={selectedGenre.slug}
                src={selectedGenre.featuredMovie.posterUrl}
                alt=""
                className="absolute inset-0 -z-30 h-full w-full animate-in fade-in duration-500 object-cover object-center"
              />
            )}
            <div className="absolute inset-0 -z-20 bg-gradient-to-r from-[#10182d]/95 via-[#10182d]/70 to-[#10182d]/25" />
            <div className="absolute inset-0 -z-20 bg-gradient-to-t from-[#10182d] via-[#10182d]/20 to-black/15" />
            <div className={`absolute inset-0 -z-10 bg-gradient-to-br ${selectedVisual.glow} to-transparent opacity-60`} />

            <div key={selectedGenre.slug} className="max-w-2xl animate-in fade-in slide-in-from-bottom-2 duration-300">
              <div
                className={`mb-5 flex h-13 w-13 items-center justify-center rounded-2xl border backdrop-blur-md ${selectedVisual.iconBackground} ${selectedVisual.accent}`}
              >
                <SelectedIcon className="h-6 w-6" strokeWidth={1.8} />
              </div>
              <p className="text-xs font-bold uppercase tracking-[0.18em] text-white/55">
                {selectedGenre.moviesCount}{' '}
                {selectedGenre.moviesCount === 1 ? 'película disponible' : 'películas disponibles'}
              </p>
              <h2 className="mt-2 text-4xl font-black tracking-tight text-white sm:text-6xl">
                {selectedGenre.name}
              </h2>

              {selectedGenre.featuredMovie && (
                <div className="mt-6 border-l-2 border-[#e94560] pl-4">
                  <span className="text-[11px] font-bold uppercase tracking-wider text-white/45">
                    Recomendación destacada
                  </span>
                  <Link
                    to={`/peliculas/${selectedGenre.featuredMovie.id}`}
                    className="mt-1 flex w-fit items-center gap-2 text-lg font-bold text-white transition-colors hover:text-[#ff7187]"
                  >
                    {selectedGenre.featuredMovie.title}
                    <ArrowUpRight className="h-4 w-4" />
                  </Link>
                </div>
              )}

              <Link
                to={`/explorar?genre=${selectedGenre.slug}`}
                className="mt-8 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[#e94560] px-5 text-sm font-bold text-white shadow-lg shadow-[#e94560]/20 transition-all hover:-translate-y-0.5 hover:bg-[#f0526b]"
              >
                Explorar {selectedGenre.name}
                <ArrowUpRight className="h-4 w-4" />
              </Link>
            </div>
          </section>
        </div>
      )}
    </div>
  );
}
