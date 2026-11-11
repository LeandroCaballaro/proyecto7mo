import { useEffect, useState } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Filter, PlusCircle, Search, SlidersHorizontal, X } from 'lucide-react';
import { GENRES, type ApiPaginated, type MovieDto } from '@nexohub/shared';
import { Button, Combobox, Input, Pagination, Skeleton } from '../components/ui';
import { MovieCard } from '../features/movies/MovieCard';
import { apiClient } from '../lib/api-client';

export function ExplorePage() {
  const [searchParams, setSearchParams] = useSearchParams();

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

  return (
    <div className="space-y-8 pb-16">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl sm:text-3xl font-black text-[#f5f5f5]">Explorar Catálogo</h1>
        <p className="text-xs sm:text-sm text-[#a0a0a0] mt-1">
          Busca películas, filtra por categoría y descubre qué opinan otros cinéfilos.
        </p>
      </div>

      {/* Filters Toolbar */}
      <div className="p-5 rounded-2xl bg-[#16213e] border border-[#0f3460] space-y-4 shadow-xl">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
          {/* Search Input con componente reutilizable Input */}
          <div className="lg:col-span-2">
            <Input
              placeholder="Buscar por título o director..."
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
                    className="p-1 hover:text-white transition-colors cursor-pointer"
                  >
                    <X className="h-3.5 w-3.5" />
                  </button>
                ) : undefined
              }
            />
          </div>

          {/* Genre Combobox reutilizable */}
          <Combobox
            options={genreOptions}
            value={selectedGenre}
            onChange={(val) => updateQuery('genre', val)}
            placeholder="Todos los géneros"
            searchPlaceholder="Filtrar género..."
          />

          {/* Sort Selector con Combobox reutilizable */}
          <Combobox
            options={sortOptions}
            value={currentSort}
            onChange={(val) => updateQuery('sort', val)}
            placeholder="Ordenar por..."
            clearable={false}
            searchable={false}
          />
        </div>

        {/* Quick Filter Pills */}
        <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
          <span className="text-[#a0a0a0] flex items-center gap-1 shrink-0 mr-1 font-medium">
            <Filter className="h-3 w-3 text-[#e94560]" /> Géneros rápidos:
          </span>
          {GENRES.map((g) => {
            const isSelected = selectedGenre === g.slug;
            return (
              <button
                key={g.slug}
                type="button"
                onClick={() => updateQuery('genre', isSelected ? '' : g.slug)}
                className={`px-3 py-1 rounded-full border text-xs font-semibold whitespace-nowrap transition-all cursor-pointer ${
                  isSelected
                    ? 'bg-[#e94560] text-white border-[#e94560] shadow-md shadow-[#e94560]/30'
                    : 'bg-[#0f172a] text-[#cbd5e1] border-[#0f3460] hover:text-white hover:border-[#e94560]/50'
                }`}
              >
                {g.name}
              </button>
            );
          })}
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
            ¿La película no está en nuestro catálogo? Búscala en el catálogo externo y sé el primero en reseñarla.
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
