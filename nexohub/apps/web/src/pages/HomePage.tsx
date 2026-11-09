import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import {
  ArrowRight,
  Award,
  Compass,
  Film,
  Play,
  ShieldCheck,
  Star,
  TrendingUp,
  Users,
  Zap,
} from 'lucide-react';
import { GENRES, type MovieDto } from '@nexohub/shared';
import { MovieCard } from '../features/movies/MovieCard';
import { apiClient } from '../lib/api-client';

export function HomePage({ onOpenPropose }: { onOpenPropose: () => void }) {
  const { data: featuredMovies, isLoading } = useQuery({
    queryKey: ['movies', 'featured'],
    queryFn: async () => {
      const res = await apiClient<{ data: MovieDto[] }>('/movies/featured?limit=8');
      return res.data;
    },
  });

  const { data: stats } = useQuery({
    queryKey: ['stats', 'summary'],
    queryFn: async () => {
      const res = await apiClient<{
        data: {
          usersCount: number;
          reviewsCount: number;
          moviesCount: number;
          genresCount: number;
        };
      }>('/stats/summary');
      return res.data;
    },
  });

  const { data: genresData } = useQuery({
    queryKey: ['movies', 'genres'],
    queryFn: async () => {
      const res = await apiClient<{ data: Array<{ id: number; name: string; slug: string; moviesCount?: number }> }>(
        '/movies/genres',
      );
      return res.data;
    },
  });

  const previewGenres = (genresData && genresData.length > 0 ? genresData : GENRES).slice(0, 6);

  return (
    <div className="-mx-4 sm:-mx-6 -mt-6 space-y-0">
      {/* ──────────────── 1. HERO SECTION ORIGINAL ──────────────── */}
      <section className="relative overflow-hidden py-16 lg:py-24 px-4 sm:px-6">
        <div className="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          {/* Columna Izquierda: Textos y CTAs */}
          <div className="lg:col-span-7 space-y-6">
            {/* Pill superior */}
            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-[#e94560]/40 bg-[#e94560]/10 text-xs font-semibold text-[#e94560]">
              <Star className="h-3.5 w-3.5" />
              <span>Sistema de reputación por género</span>
            </div>

            {/* Título Principal */}
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-black text-[#f5f5f5] leading-tight tracking-tight">
              Conecta con el cine a través de{' '}
              <span className="text-[#e94560]">reseñas confiables</span>
            </h1>

            {/* Subtítulo */}
            <p className="text-base sm:text-lg text-[#a0a0a0] max-w-xl leading-relaxed">
              Descubre películas basándote en opiniones de usuarios especializados en cada género.
              Comparte tus reseñas y construye tu reputación como crítico de cine.
            </p>

            {/* Botones de Acción */}
            <div className="flex flex-col sm:flex-row gap-4 pt-2">
              <Link
                to="/explorar"
                className="inline-flex items-center justify-center gap-2.5 bg-[#e94560] text-white hover:bg-[#e94560]/90 px-6 py-3.5 rounded-lg text-base font-semibold shadow-lg shadow-[#e94560]/25 transition-all"
              >
                <Play className="h-4 w-4 fill-white" />
                Comenzar Ahora
              </Link>

              <Link
                to="/ranking"
                className="inline-flex items-center justify-center border border-[#0f3460] bg-[#16213e] text-[#f5f5f5] hover:bg-[#0f3460] px-6 py-3.5 rounded-lg text-base font-semibold transition-all"
              >
                Ver Ranking
              </Link>
            </div>

            {/* Contadores / Stats Dinámicos */}
            <div className="flex flex-wrap gap-8 pt-6 border-t border-[#0f3460]/60">
              <div className="space-y-0.5">
                <div className="flex items-center gap-2">
                  <Users className="h-5 w-5 text-[#e94560]" />
                  <span className="text-2xl font-black text-[#f5f5f5]">
                    {stats ? stats.usersCount : '...'}
                  </span>
                </div>
                <p className="text-xs text-[#a0a0a0]">Usuarios Activos</p>
              </div>

              <div className="space-y-0.5">
                <div className="flex items-center gap-2">
                  <Star className="h-5 w-5 text-[#e94560]" />
                  <span className="text-2xl font-black text-[#f5f5f5]">
                    {stats ? stats.reviewsCount : '...'}
                  </span>
                </div>
                <p className="text-xs text-[#a0a0a0]">Reseñas</p>
              </div>

              <div className="space-y-0.5">
                <div className="flex items-center gap-2">
                  <TrendingUp className="h-5 w-5 text-[#e94560]" />
                  <span className="text-2xl font-black text-[#f5f5f5]">
                    {stats ? stats.genresCount : '...'}
                  </span>
                </div>
                <p className="text-xs text-[#a0a0a0]">Géneros</p>
              </div>
            </div>
          </div>

          {/* Columna Derecha: Imagen Oficial NexoHub con Aura Glow */}
          <div className="lg:col-span-5 relative flex items-center justify-center">
            {/* Círculo resplandor en fondo */}
            <div className="absolute inset-0 bg-[#e94560]/15 rounded-full blur-3xl opacity-70 pointer-events-none" />
            <img
              src="/nexohub-logo.png"
              alt="NexoHub Logo"
              className="nexologo relative z-10"
            />
          </div>
        </div>
      </section>

      {/* ──────────────── 2. ¿POR QUÉ NEXOHUB? (FEATURES SECTION) ──────────────── */}
      <section className="bg-[#16213e] py-20 px-4 sm:px-6 border-y border-[#0f3460]">
        <div className="max-w-7xl mx-auto space-y-12">
          <div className="text-center space-y-3">
            <h2 className="text-3xl sm:text-4xl font-extrabold text-[#f5f5f5]">
              ¿Por qué <span className="text-[#e94560]">NexoHub</span>?
            </h2>
            <p className="text-sm sm:text-base text-[#a0a0a0] max-w-2xl mx-auto">
              Un sistema diseñado para que descubras el mejor contenido según tu género favorito.
            </p>
          </div>

          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {/* Feature 1 */}
            <div className="rounded-xl border border-[#0f3460] bg-[#1a1a2e] p-6 transition-all hover:border-[#e94560]/50 hover:shadow-lg hover:shadow-[#e94560]/5 group">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#e94560]/10 text-[#e94560]">
                <ShieldCheck className="h-6 w-6" />
              </div>
              <h3 className="mb-2 text-lg font-bold text-[#f5f5f5]">Reputación por Género</h3>
              <p className="text-xs text-[#a0a0a0] leading-relaxed">
                Cada usuario construye su reputación en géneros específicos, asegurando que las
                reseñas vengan de verdaderos conocedores.
              </p>
            </div>

            {/* Feature 2 */}
            <div className="rounded-xl border border-[#0f3460] bg-[#1a1a2e] p-6 transition-all hover:border-[#e94560]/50 hover:shadow-lg hover:shadow-[#e94560]/5 group">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#e94560]/10 text-[#e94560]">
                <Users className="h-6 w-6" />
              </div>
              <h3 className="mb-2 text-lg font-bold text-[#f5f5f5]">Comunidad Activa</h3>
              <p className="text-xs text-[#a0a0a0] leading-relaxed">
                Conecta con otros cinéfilos, sigue a tus críticos favoritos y descubre películas
                recomendadas por personas con gustos similares.
              </p>
            </div>

            {/* Feature 3 */}
            <div className="rounded-xl border border-[#0f3460] bg-[#1a1a2e] p-6 transition-all hover:border-[#e94560]/50 hover:shadow-lg hover:shadow-[#e94560]/5 group">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#e94560]/10 text-[#e94560]">
                <Zap className="h-6 w-6" />
              </div>
              <h3 className="mb-2 text-lg font-bold text-[#f5f5f5]">Descubrimiento Preciso</h3>
              <p className="text-xs text-[#a0a0a0] leading-relaxed">
                Encuentra películas que realmente te gustarán gracias a nuestro sistema de filtrado
                por género y reputación del reseñador.
              </p>
            </div>

            {/* Feature 4 */}
            <div className="rounded-xl border border-[#0f3460] bg-[#1a1a2e] p-6 transition-all hover:border-[#e94560]/50 hover:shadow-lg hover:shadow-[#e94560]/5 group">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#e94560]/10 text-[#e94560]">
                <Award className="h-6 w-6" />
              </div>
              <h3 className="mb-2 text-lg font-bold text-[#f5f5f5]">Críticas Verificadas</h3>
              <p className="text-xs text-[#a0a0a0] leading-relaxed">
                Un sistema transparente donde cada valoración cuenta y es respaldada por los
                corazones de los cinéfilos de la plataforma.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* ──────────────── 3. EXPLORA POR GÉNERO (GENRES SECTION) ──────────────── */}
      <section className="py-20 px-4 sm:px-6">
        <div className="max-w-7xl mx-auto space-y-12">
          <div className="text-center space-y-3">
            <h2 className="text-3xl sm:text-4xl font-extrabold text-[#f5f5f5]">
              Explora por Género
            </h2>
            <p className="text-sm sm:text-base text-[#a0a0a0] max-w-2xl mx-auto">
              Entrá a una categoría y encontrá películas, reseñas y usuarios con reputación en ese
              género.
            </p>
          </div>

          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {previewGenres.map((g) => (
              <Link
                key={g.slug}
                to={`/explorar?genre=${g.slug}`}
                className="group rounded-xl border border-[#0f3460] bg-[#16213e] p-6 transition-all hover:border-[#e94560] hover:shadow-lg hover:-translate-y-1 transform duration-300 block"
              >
                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#e94560]/10 group-hover:bg-[#e94560]/20 transition-colors">
                  <span className="text-xl font-black text-[#e94560]">
                    {g.name.charAt(0).toUpperCase()}
                  </span>
                </div>
                <h3 className="mb-1 text-xl font-bold text-[#f5f5f5] group-hover:text-[#e94560] transition-colors">
                  {g.name}
                </h3>
                <p className="text-[#a0a0a0] text-xs">
                  Ver películas y reseñas de {g.name}.
                </p>
              </Link>
            ))}
          </div>

          <div className="text-center pt-4">
            <Link
              to="/generos"
              className="inline-flex items-center justify-center rounded-lg bg-[#e94560] px-6 py-3 font-bold text-white hover:bg-[#e94560]/90 shadow-md shadow-[#e94560]/20 transition-all"
            >
              Ver todos los géneros
            </Link>
          </div>
        </div>
      </section>

      {/* ──────────────── 4. PELÍCULAS DESTACADAS ──────────────── */}
      <section className="bg-[#16213e] py-16 px-4 sm:px-6 border-y border-[#0f3460]">
        <div className="max-w-7xl mx-auto space-y-8">
          <div className="text-center space-y-2">
            <h2 className="text-3xl sm:text-4xl font-extrabold text-[#f5f5f5]">
              Películas Destacadas
            </h2>
            <p className="text-sm text-[#a0a0a0] max-w-2xl mx-auto">
              Las películas más valoradas por nuestra comunidad de expertos.
            </p>
          </div>

          {isLoading ? (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
              {Array.from({ length: 8 }).map((_, i) => (
                <div key={i} className="h-64 rounded-xl bg-[#1a1a2e] animate-pulse" />
              ))}
            </div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
              {featuredMovies?.map((movie) => (
                <MovieCard key={movie.id} movie={movie} />
              ))}
            </div>
          )}

          <div className="text-center pt-4">
            <Link
              to="/explorar?sort=rating"
              className="inline-flex items-center gap-1.5 text-sm font-semibold text-[#e94560] hover:text-[#e94560]/80 transition-colors"
            >
              Ver catálogo completo <ArrowRight className="h-4 w-4" />
            </Link>
          </div>
        </div>
      </section>

      {/* ──────────────── 5. ÚNETE A LA COMUNIDAD (CTA SECTION) ──────────────── */}
      <section className="bg-[#e94560] py-20 px-4 sm:px-6 text-center">
        <div className="max-w-3xl mx-auto space-y-6">
          <h2 className="text-3xl sm:text-4xl font-black text-white">
            Únete a la Comunidad
          </h2>
          <p className="text-base sm:text-lg text-white/90">
            Comienza a compartir tus reseñas y construye tu reputación como crítico de cine.
          </p>
          <div className="pt-2 flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              to="/explorar"
              className="inline-flex items-center justify-center border-2 border-white text-white hover:bg-white/10 px-8 py-3 rounded-lg text-base font-bold transition-all"
            >
              Explorar Películas
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}
