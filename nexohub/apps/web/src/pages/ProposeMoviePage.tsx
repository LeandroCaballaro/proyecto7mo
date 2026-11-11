import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Calendar,
  CheckCircle2,
  Clapperboard,
  Film,
  Loader2,
  RotateCcw,
  Search,
  Send,
  ShieldCheck,
  Star,
  X,
} from 'lucide-react';
import { toast } from 'sonner';
import { GENRES, type CreateMovieInput } from '@nexohub/shared';
import { Badge, Button, Combobox, Input, Skeleton, StarRating } from '../components/ui';
import { apiClient } from '../lib/api-client';
import { useAuthStore } from '../stores/auth.store';

interface ExternalSearchResult {
  imdbId: string;
  title: string;
  releaseYear: number | null;
  posterUrl: string | null;
  description: string | null;
}

interface ExternalMovieMeta {
  imdbId: string;
  title: string;
  releaseYear: number | null;
  director: string | null;
  genres: string[];
  genreSlug: string;
  synopsis: string | null;
  posterUrl: string | null;
  imdbRating: string | null;
}

export function ProposeMoviePage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { user } = useAuthStore();

  // Estados del buscador IMDb
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [selectedImdbId, setSelectedImdbId] = useState<string | null>(null);

  // Estados del formulario y reseña
  const [selectedGenreSlug, setSelectedGenreSlug] = useState('drama');
  const [rating, setRating] = useState(5);
  const [reviewContent, setReviewContent] = useState('');

  // Debounce para búsqueda
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchTerm.trim());
    }, 350);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  // Query: Resultados de búsqueda en IMDb / Cinemeta
  const {
    data: searchResults,
    isLoading: isSearching,
  } = useQuery({
    queryKey: ['external-movies-search', debouncedSearch],
    queryFn: async () => {
      if (!debouncedSearch || debouncedSearch.length < 2) return [];
      const res = await apiClient<{ data: ExternalSearchResult[] }>(
        `/movies/external/search?q=${encodeURIComponent(debouncedSearch)}`,
      );
      return res.data;
    },
    enabled: Boolean(debouncedSearch && debouncedSearch.length >= 2 && !selectedImdbId),
  });

  // Query: Metadata completa de la película seleccionada
  const {
    data: selectedMovieMeta,
    isLoading: isLoadingMeta,
  } = useQuery({
    queryKey: ['external-movie-meta', selectedImdbId],
    queryFn: async () => {
      if (!selectedImdbId) return null;
      const res = await apiClient<{ data: ExternalMovieMeta }>(
        `/movies/external/${selectedImdbId}`,
      );
      return res.data;
    },
    enabled: Boolean(selectedImdbId),
  });

  // Sincronizar el género mapeado cuando se carga la metadata de IMDb
  useEffect(() => {
    if (selectedMovieMeta?.genreSlug) {
      setSelectedGenreSlug(selectedMovieMeta.genreSlug);
    }
  }, [selectedMovieMeta]);

  // Mutación: Publicar película y reseña inicial
  const createMovieMutation = useMutation({
    mutationFn: async (payload: CreateMovieInput) => {
      return apiClient<{ data: { movie: { id: number; title: string }; message: string } }>(
        '/movies',
        {
          method: 'POST',
          body: JSON.stringify(payload),
        },
      );
    },
    onSuccess: (res) => {
      toast.success(res.data.message || 'Película y reseña publicadas con éxito');
      queryClient.invalidateQueries({ queryKey: ['movies'] });
      queryClient.invalidateQueries({ queryKey: ['reviews'] });
      navigate(`/peliculas/${res.data.movie.id}`);
    },
    onError: (err: any) => {
      toast.error(err.message || 'Error al incorporar la película al catálogo');
    },
  });

  function handleSelectMovie(imdbId: string) {
    setSelectedImdbId(imdbId);
    setSearchTerm('');
  }

  function handleResetSelection() {
    setSelectedImdbId(null);
    setReviewContent('');
    setRating(5);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!user) {
      toast.error('Debes iniciar sesión para publicar y reseñar');
      return;
    }
    if (!selectedMovieMeta) {
      toast.error('Debes seleccionar una película verificada de la lista');
      return;
    }
    if (reviewContent.trim().length < 3) {
      toast.error('La reseña debe tener al menos 3 caracteres');
      return;
    }

    createMovieMutation.mutate({
      title: selectedMovieMeta.title,
      genreSlug: selectedGenreSlug,
      releaseYear: selectedMovieMeta.releaseYear || undefined,
      director: selectedMovieMeta.director || undefined,
      synopsis: selectedMovieMeta.synopsis || undefined,
      posterUrl: selectedMovieMeta.posterUrl || undefined,
      imdbId: selectedMovieMeta.imdbId,
      initialReview: {
        rating,
        content: reviewContent.trim(),
      },
    } as any);
  }

  const genreOptions = GENRES.map((g) => ({
    value: g.slug,
    label: g.name,
  }));

  const ratingDescriptions: Record<number, string> = {
    1: 'Mala',
    2: 'Regular',
    3: 'Buena',
    4: 'Muy buena',
    5: 'Excelente obra maestra',
  };

  const fallbackPoster = 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=500&auto=format&fit=crop&q=80';

  return (
    <div className="space-y-8 pb-16 pt-2 max-w-6xl mx-auto">
      {/* Encabezado del Apartado */}
      <header className="border-b border-[#0f3460]/60 pb-6">
        <div className="flex items-center gap-2 mb-2">
          <Clapperboard className="h-6 w-6 text-[#e94560]" />
          <span className="text-xs font-bold uppercase tracking-widest text-[#e94560]">
            Catálogo Oficial & Comunidad
          </span>
        </div>
        <h1 className="text-3xl sm:text-4xl font-black text-[#f5f5f5] tracking-tight">
          Reseñar Película Fuera de Catálogo
        </h1>
        <p className="text-sm text-[#a0a0a0] mt-2 max-w-2xl leading-relaxed">
          ¿La película que deseas calificar no está en NexoHub? Búscala en la base de datos oficial
          de IMDb. La información (póster oficial, director, año y sinopsis) se verificará
          automáticamente para garantizar la legitimidad del catálogo.
        </p>
      </header>

      {/* ========================================================= */}
      {/* PASO 1: BUSCADOR EN VIVO DE IMDB                          */}
      {/* ========================================================= */}
      {!selectedImdbId && (
        <section className="bg-[#16213e] border border-[#0f3460] rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
          <div className="space-y-2">
            <h2 className="text-lg font-bold text-white flex items-center gap-2">
              <Search className="h-5 w-5 text-[#e94560]" />
              Paso 1: Busca la película en la base de datos de IMDb
            </h2>
            <p className="text-xs text-[#a0a0a0]">
              Escribe el título exacto o una palabra clave en español o en su idioma original.
            </p>
          </div>

          <div className="relative">
            <Input
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Ej. Interstellar, Oppenheimer, El Padrino, Parasite, Arrival..."
              leftIcon={<Search className="h-4 w-4" />}
              rightIcon={
                isSearching ? (
                  <Loader2 className="h-4 w-4 animate-spin text-[#e94560]" />
                ) : searchTerm ? (
                  <button
                    type="button"
                    onClick={() => setSearchTerm('')}
                    className="p-1 hover:text-white cursor-pointer"
                  >
                    <X className="h-3.5 w-3.5" />
                  </button>
                ) : undefined
              }
              className="!py-3.5 !text-base"
              autoFocus
            />
          </div>

          {/* Resultados de búsqueda */}
          {debouncedSearch && debouncedSearch.length >= 2 && (
            <div className="space-y-3 pt-2">
              <div className="flex items-center justify-between text-xs text-[#a0a0a0]">
                <span>
                  Resultados encontrados para <strong className="text-white">"{debouncedSearch}"</strong>
                </span>
                <span className="text-[11px] font-semibold text-[#e94560] uppercase">
                  Verificación IMDb Activa
                </span>
              </div>

              {isSearching ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                  {Array.from({ length: 6 }).map((_, i) => (
                    <div key={i} className="flex gap-3 p-3 bg-[#0f172a] rounded-xl border border-[#0f3460] animate-pulse">
                      <Skeleton className="w-16 h-24 rounded-lg shrink-0" />
                      <div className="space-y-2 flex-1">
                        <Skeleton className="h-4 w-3/4 rounded" />
                        <Skeleton className="h-3 w-1/2 rounded" />
                      </div>
                    </div>
                  ))}
                </div>
              ) : searchResults && searchResults.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[480px] overflow-y-auto pr-1">
                  {searchResults.map((movie) => (
                    <div
                      key={movie.imdbId}
                      onClick={() => handleSelectMovie(movie.imdbId)}
                      className="group flex gap-3.5 p-3 rounded-xl bg-[#0f172a] border border-[#0f3460] hover:border-[#e94560] transition-all duration-200 cursor-pointer shadow-md hover:-translate-y-0.5"
                    >
                      <div className="w-16 h-24 rounded-lg overflow-hidden bg-[#16213e] shrink-0 border border-[#0f3460]/70">
                        <img
                          src={movie.posterUrl || fallbackPoster}
                          alt={movie.title}
                          className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                          onError={(e) => {
                            (e.currentTarget as HTMLImageElement).src = fallbackPoster;
                          }}
                        />
                      </div>

                      <div className="flex flex-col justify-between min-w-0 flex-1 py-0.5">
                        <div className="space-y-1">
                          <h3 className="text-sm font-bold text-white group-hover:text-[#e94560] transition-colors truncate">
                            {movie.title}
                          </h3>
                          <div className="flex items-center gap-2 text-xs text-[#a0a0a0]">
                            {movie.releaseYear && (
                              <span className="flex items-center gap-1">
                                <Calendar className="h-3 w-3 text-[#e94560]" />
                                {movie.releaseYear}
                              </span>
                            )}
                            <Badge variant="zinc" size="sm">
                              {movie.imdbId}
                            </Badge>
                          </div>
                        </div>

                        <Button
                          variant="secondary"
                          size="sm"
                          className="w-full !py-1 !text-xs group-hover:!bg-[#e94560] group-hover:!text-white group-hover:!border-[#e94560] transition-all"
                        >
                          Seleccionar
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="py-12 text-center bg-[#0f172a] rounded-xl border border-[#0f3460] space-y-2">
                  <Film className="h-8 w-8 text-[#a0a0a0] mx-auto opacity-40" />
                  <p className="text-sm text-[#cbd5e1] font-medium">
                    No se encontraron películas en IMDb con ese término.
                  </p>
                  <p className="text-xs text-[#a0a0a0]">
                    Prueba buscando por el título en inglés o revisando la ortografía.
                  </p>
                </div>
              )}
            </div>
          )}
        </section>
      )}

      {/* ========================================================= */}
      {/* PASO 2: PELÍCULA VERIFICADA + FORMULARIO DE RESEÑA         */}
      {/* ========================================================= */}
      {selectedImdbId && (
        <div className="space-y-6 animate-in fade-in-50 duration-300">
          {/* Botón de Selección / Reemplazo */}
          <div className="flex items-center justify-between bg-[#16213e] border border-[#0f3460] rounded-xl px-4 py-3">
            <div className="flex items-center gap-2 text-xs text-[#cbd5e1]">
              <CheckCircle2 className="h-4 w-4 text-emerald-400" />
              <span>
                Película seleccionada y verificada contra base de datos oficial de IMDb.
              </span>
            </div>
            <Button
              variant="outline"
              size="sm"
              onClick={handleResetSelection}
              leftIcon={<RotateCcw className="h-3.5 w-3.5" />}
            >
              Cambiar Película
            </Button>
          </div>

          {isLoadingMeta ? (
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start py-6 animate-pulse">
              <div className="lg:col-span-5 space-y-4">
                <Skeleton className="aspect-[2/3] w-full rounded-2xl" />
                <Skeleton className="h-8 w-3/4 rounded-lg" />
              </div>
              <div className="lg:col-span-7 space-y-4">
                <Skeleton className="h-32 w-full rounded-xl" />
                <Skeleton className="h-48 w-full rounded-xl" />
              </div>
            </div>
          ) : selectedMovieMeta ? (
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
              {/* Tarjeta de la Película Verificada (Columna Izquierda) */}
              <aside className="lg:col-span-5 lg:sticky lg:top-24">
                <div className="bg-[#16213e] border border-[#0f3460] rounded-2xl p-6 sm:p-7 shadow-2xl relative overflow-hidden space-y-5">
                  {/* Badge de Verificación IMDb */}
                  <div className="flex items-center justify-between">
                    <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold">
                      <ShieldCheck className="h-3.5 w-3.5" />
                      <span>Verificada IMDb: {selectedMovieMeta.imdbId}</span>
                    </div>
                    {selectedMovieMeta.imdbRating && (
                      <div className="flex items-center gap-1 text-xs font-bold text-amber-400">
                        <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                        <span>{selectedMovieMeta.imdbRating} / 10</span>
                      </div>
                    )}
                  </div>

                  {/* Poster Oficial */}
                  <div className="relative aspect-[2/3] w-full rounded-xl overflow-hidden shadow-2xl border border-[#0f3460]/70 bg-[#1a1a2e]">
                    <img
                      src={selectedMovieMeta.posterUrl || fallbackPoster}
                      alt={selectedMovieMeta.title}
                      className="h-full w-full object-cover"
                      onError={(e) => {
                        (e.currentTarget as HTMLImageElement).src = fallbackPoster;
                      }}
                    />
                  </div>

                  {/* Info Principal */}
                  <div className="space-y-2">
                    <h2 className="text-2xl font-black text-white tracking-tight">
                      {selectedMovieMeta.title}
                    </h2>
                    <div className="flex flex-wrap items-center gap-2 text-xs text-[#a0a0a0]">
                      {selectedMovieMeta.releaseYear && (
                        <span className="flex items-center gap-1 font-medium">
                          <Calendar className="h-3.5 w-3.5 text-[#e94560]" />
                          {selectedMovieMeta.releaseYear}
                        </span>
                      )}
                      {selectedMovieMeta.director && (
                        <>
                          <span className="text-[#0f3460]">•</span>
                          <span>
                            Dirigida por <strong className="text-white">{selectedMovieMeta.director}</strong>
                          </span>
                        </>
                      )}
                    </div>
                  </div>

                  {/* Sinopsis Oficial */}
                  {selectedMovieMeta.synopsis && (
                    <div className="space-y-1 pt-2 border-t border-[#0f3460]/40">
                      <h4 className="text-[11px] font-bold uppercase tracking-wider text-[#a0a0a0]">
                        Sinopsis Oficial
                      </h4>
                      <p className="text-xs text-[#cbd5e1] leading-relaxed">
                        {selectedMovieMeta.synopsis}
                      </p>
                    </div>
                  )}
                </div>
              </aside>

              {/* Formulario de Reseña y Publicación (Columna Derecha) */}
              <section className="lg:col-span-7 bg-[#16213e] border border-[#0f3460] rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6">
                <div className="space-y-1">
                  <h3 className="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                    <Star className="h-5 w-5 text-amber-400 fill-amber-400" />
                    Paso 2: Escribe tu reseña para esta película
                  </h3>
                  <p className="text-xs text-[#a0a0a0]">
                    Esta película se incorporará al catálogo de NexoHub con su información oficial
                    y tu reseña será la primera visible para la comunidad.
                  </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-5">
                  {/* Categoría / Género en NexoHub */}
                  <div className="space-y-1.5">
                    <label className="block text-xs font-bold text-[#a0a0a0] uppercase tracking-wider">
                      Categoría / Género en NexoHub
                    </label>
                    <Combobox
                      options={genreOptions}
                      value={selectedGenreSlug}
                      onChange={setSelectedGenreSlug}
                      placeholder="Seleccionar género..."
                      clearable={false}
                      searchable={false}
                    />
                    <p className="text-[11px] text-[#a0a0a0]">
                      Auto-detectado de los géneros oficiales de IMDb:{' '}
                      <strong className="text-white">
                        {selectedMovieMeta.genres?.join(', ') || 'General'}
                      </strong>
                    </p>
                  </div>

                  {/* Calificación con estrellas */}
                  <div className="space-y-1.5 pt-2">
                    <label className="block text-xs font-bold text-[#a0a0a0] uppercase tracking-wider">
                      Tu Calificación
                    </label>
                    <div className="flex items-center gap-3">
                      <StarRating value={rating} onChange={setRating} size="lg" />
                      <span className="text-xs font-bold text-white">
                        {ratingDescriptions[rating] || ''}
                      </span>
                    </div>
                  </div>

                  {/* Texto de la Reseña */}
                  <div className="space-y-1.5">
                    <label className="block text-xs font-bold text-[#a0a0a0] uppercase tracking-wider">
                      Tu Reseña y Crítica
                    </label>
                    <textarea
                      value={reviewContent}
                      onChange={(e) => setReviewContent(e.target.value)}
                      placeholder="¿Qué te pareció la dirección, las actuaciones, el guion o la fotografía? Comparte tu análisis..."
                      rows={5}
                      required
                      minLength={3}
                      className="w-full bg-[#0f172a] border border-[#0f3460] focus:border-[#e94560] rounded-xl p-3.5 text-sm text-white placeholder-[#64748b] outline-none transition-colors resize-y min-h-[120px]"
                    />
                  </div>

                  {/* Botón de Envío */}
                  <div className="pt-2">
                    {user ? (
                      <Button
                        type="submit"
                        variant="primary"
                        size="lg"
                        className="w-full"
                        isLoading={createMovieMutation.isPending}
                        leftIcon={<Send className="h-4 w-4" />}
                      >
                        {createMovieMutation.isPending
                          ? 'Verificando y publicando...'
                          : 'Publicar Película y Reseña'}
                      </Button>
                    ) : (
                      <div className="p-4 rounded-xl bg-[#0f172a] border border-[#0f3460] text-center space-y-2">
                        <p className="text-xs text-[#cbd5e1]">
                          Debes iniciar sesión para publicar esta película y tu reseña.
                        </p>
                        <div className="flex justify-center gap-3">
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
                </form>
              </section>
            </div>
          ) : null}
        </div>
      )}
    </div>
  );
}

