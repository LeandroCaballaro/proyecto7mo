import { useEffect, useRef, useState } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
  ChevronLeft,
  ChevronRight,
  PlusCircle,
  RotateCcw,
  Search,
  SlidersHorizontal,
  X,
} from 'lucide-react';
import { GENRES, type ApiPaginated, type MovieDto } from '@nexohub/shared';
import { Button, Combobox, Input, Pagination, Skeleton } from '../components/ui';
import { MovieCard } from '../features/movies/MovieCard';
import { apiClient } from '../lib/api-client';

export function ExplorePage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const genresRailRef = useRef<HTMLDivElement>(null);

  const [q, setQ] = useState(searchParams.get('q') || '');
  const [debouncedQ, setDebouncedQ] = useState(q);
  const selectedGenre = searchParams.get('genre') || '';
  const currentSort = searchParams.get('sort') || 'newest';
  const currentPage = Number(searchParams.get('page') || '1');

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedQ(q);
    }, 400);
    return () => clearTimeout(handler);
  }, [q]);

  const { data, isLoading } = useQuery({
    queryKey: ['movies', 'list', debouncedQ, selectedGenre, currentSort, currentPage],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: String(currentPage),
        pageSize: '12',
        sort: currentSort,
      });
      if (debouncedQ) params.set('q', debouncedQ);
      if (selectedGenre) params.set('genre', selectedGenre);

      return apiClient<ApiPaginated<MovieDto>>(`/movies?${params.toString()}`);
    },
  });

  function updateQuery(key: string, val: string) {
    const next = new URLSearchParams(searchParams);
    if (val) {
      next.set(key, val);
    } else {
      next.delete(key);
    }
    if (key !== 'page') next.set('page', '1');
    setSearchParams(next);
  }

  const genreOptions = [
    { value: '', label: 'Todos los géneros' },
    ...GENRES.map((g) => ({ value: g.slug, label: g.name })),
  ];

  const sortOptions = [
    { value: 'newest', label: 'Más recientes' },
    { value: 'rating', label: 'Mayor calificación' },
    { value: 'reviews', label: 'Más reseñas' },
    { value: 'year', label: 'Año de estreno' },
  ];

  const activeFilters =
    Number(Boolean(q)) + Number(Boolean(selectedGenre)) + Number(currentSort !== 'newest');

  function resetFilters() {
    setQ('');
    setDebouncedQ('');
    setSearchParams(new URLSearchParams());
  }

  function scrollGenres(direction: number) {
    genresRailRef.current?.scrollBy({ left: direction * 420, behavior: 'smooth' });
  }

  return (
    <div className="space-y-7 pb-16">
      <div className="flex flex-col gap-4 border-b border-[#0f3460]/60 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div className="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.18em] text-[#e94560]">
            <SlidersHorizontal className="h-3.5 w-3.5" /> Catálogo NexoHub
          </div>
          <h1 className="text-3xl font-black tracking-tight text-[#f5f5f5] sm:text-4xl">
            Encontrá algo que valga la pena ver
          </h1>
          <p className="mt-2 text-sm text-[#a0a0a0]">
            Buscá, combiná filtros y ordená el catálogo a tu manera.
          </p>
        </div>
        {activeFilters > 0 && (
          <button
            type="button"
            onClick={resetFilters}
            className="inline-flex items-center gap-2 self-start rounded-lg px-3 py-2 text-xs font-bold text-[#a0a0a0] transition-colors hover:bg-[#16213e] hover:text-white sm:self-auto"
          >
            <RotateCcw className="h-3.5 w-3.5" /> Limpiar {activeFilters}{' '}
            {activeFilters === 1 ? 'filtro' : 'filtros'}
          </button>
        )}
      </div>

      <div className="z-30 space-y-4 rounded-2xl border border-[#0f3460] bg-[#121a31]/95 p-4 shadow-2xl shadow-black/20 backdrop-blur-xl sm:p-5 md:sticky md:top-20">
        <div className="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div className="md:col-span-6">
            <Input
              aria-label="Buscar películas"
              placeholder="Título, director o palabra clave..."
              value={q}
              onChange={(e) => {
                setQ(e.target.value);
                updateQuery('q', e.target.value);
              }}
              leftIcon={<Search className="h-4 w-4" />}
              rightIcon={
                q ? (
                  <button
                    type="button"
                    onClick={() => {
                      setQ('');
                      updateQuery('q', '');
                    }}
                    aria-label="Limpiar búsqueda"
                    className="cursor-pointer rounded-full p-1 transition-colors hover:bg-white/5 hover:text-white"
                  >
                    <X className="h-3.5 w-3.5" />
                  </button>
                ) : undefined
              }
              className="h-11 bg-[#0f172a]"
            />
          </div>
          <div className="md:col-span-3">
            <Combobox
              options={genreOptions}
              value={selectedGenre}
              onChange={(val) => updateQuery('genre', val)}
              placeholder="Todos los géneros"
              searchPlaceholder="Buscar género..."
            />
          </div>
          <div className="md:col-span-3">
            <Combobox
              options={sortOptions}
              value={currentSort}
              onChange={(val) => updateQuery('sort', val)}
              placeholder="Ordenar por..."
              clearable={false}
              searchable={false}
            />
          </div>
        </div>

        <div className="flex items-center gap-2 border-t border-white/5 pt-3">
          <button
            type="button"
            onClick={() => scrollGenres(-1)}
            aria-label="Ver géneros anteriores"
            className="hidden h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#0f3460] text-[#a0a0a0] hover:text-white sm:flex"
          >
            <ChevronLeft className="h-4 w-4" />
          </button>
          <div
            ref={genresRailRef}
            className="flex flex-1 snap-x gap-2 overflow-x-auto scrollbar-none"
          >
            <button
              type="button"
              onClick={() => updateQuery('genre', '')}
              className={`shrink-0 snap-start rounded-full border px-3.5 py-1.5 text-xs font-bold transition-all ${!selectedGenre ? 'border-[#e94560] bg-[#e94560] text-white shadow-lg shadow-[#e94560]/20' : 'border-[#0f3460] bg-[#0f172a] text-[#aeb2bf] hover:border-white/20 hover:text-white'}`}
            >
              Todos
            </button>
            {GENRES.map((genre) => {
              const selected = selectedGenre === genre.slug;
              return (
                <button
                  key={genre.slug}
                  type="button"
                  onClick={() => updateQuery('genre', selected ? '' : genre.slug)}
                  className={`shrink-0 snap-start rounded-full border px-3.5 py-1.5 text-xs font-bold transition-all ${selected ? 'border-[#e94560] bg-[#e94560] text-white shadow-lg shadow-[#e94560]/20' : 'border-[#0f3460] bg-[#0f172a] text-[#aeb2bf] hover:border-white/20 hover:text-white'}`}
                >
                  {genre.name}
                </button>
              );
            })}
          </div>
          <button
            type="button"
            onClick={() => scrollGenres(1)}
            aria-label="Ver más géneros"
            className="hidden h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#0f3460] text-[#a0a0a0] hover:text-white sm:flex"
          >
            <ChevronRight className="h-4 w-4" />
          </button>
        </div>
      </div>

      {/* Movies Grid */}
      {isLoading ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
          {Array.from({ length: 12 }).map((_, i) => (
            <div key={i} className="space-y-3">
              <Skeleton className="aspect-[2/3] w-full rounded-xl" />
              <Skeleton className="h-4 w-3/4 rounded-lg" />
              <Skeleton className="h-3 w-1/2 rounded-lg" />
            </div>
          ))}
        </div>
      ) : data?.data && data.data.length > 0 ? (
        <div className="space-y-8">
          <div className="flex items-center justify-between text-xs text-[#a0a0a0]">
            <span>
              Mostrando <strong className="text-[#f5f5f5]">{data.data.length}</strong> de{' '}
              <strong className="text-[#f5f5f5]">{data.meta.total}</strong> películas encontradas
            </span>
            <span>
              Página {data.meta.page} de {data.meta.totalPages}
            </span>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            {data.data.map((movie) => (
              <MovieCard key={movie.id} movie={movie} />
            ))}
          </div>

          <Pagination
            page={data.meta.page}
            totalPages={data.meta.totalPages}
            onPageChange={(p) => updateQuery('page', String(p))}
          />
        </div>
      ) : (
        <div className="py-20 text-center space-y-3 rounded-2xl bg-[#16213e] border border-[#0f3460]">
          <SlidersHorizontal className="h-10 w-10 text-[#a0a0a0] mx-auto opacity-50" />
          <h3 className="text-base font-semibold text-[#f5f5f5]">No se encontraron películas</h3>
          <p className="text-xs text-[#a0a0a0] max-w-sm mx-auto">
            ¿La película no está en nuestro catálogo? Búscala en el catálogo externo y sé el primero
            en reseñarla.
          </p>
          <div className="flex flex-wrap justify-center gap-3 pt-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setSearchParams(new URLSearchParams())}
            >
              Limpiar Filtros
            </Button>
            <Link to="/proponer">
              <Button variant="primary" size="sm" leftIcon={<PlusCircle className="h-4 w-4" />}>
                Buscar y reseñar
              </Button>
            </Link>
          </div>
        </div>
      )}
    </div>
  );
}
