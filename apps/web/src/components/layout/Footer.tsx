import { Film, Shield, Sparkles } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../../lib/api-client';

export function Footer() {
  const { data: movieSource } = useQuery({
    queryKey: ['config', 'movieSource'],
    queryFn: async () =>
      (await apiClient<{ data: { movieSource: 'tmdb' | 'cinemeta' } }>('/config')).data.movieSource,
    staleTime: Infinity,
  });

  return (
    <footer className="w-full border-t border-[#0f3460] bg-[#12182b] py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          <div className="space-y-3 md:col-span-2">
            <Link to="/" className="flex items-center gap-3">
              <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-[#e94560] text-white shadow-lg shadow-[#e94560]/25">
                <svg className="h-5 w-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M7 4v16l13-8L7 4z" />
                </svg>
              </div>
              <span className="text-xl font-extrabold text-[#f5f5f5] tracking-tight">
                Nexo<span className="text-[#e94560]">Hub</span>
              </span>
            </Link>
            <p className="text-xs text-[#a0a0a0] max-w-sm leading-relaxed">
              Plataforma comunitaria de cine para descubrir películas basándote en opiniones de
              usuarios especializados en cada género.
            </p>
          </div>

          <div>
            <h4 className="text-xs font-bold text-[#f5f5f5] uppercase tracking-wider mb-3">
              Comunidad
            </h4>
            <ul className="space-y-2 text-xs text-[#a0a0a0]">
              <li className="flex items-center gap-1.5">
                <Film className="h-3.5 w-3.5 text-[#e94560]" />
                Catálogo curado por género
              </li>
              <li className="flex items-center gap-1.5">
                <Sparkles className="h-3.5 w-3.5 text-[#e94560]" />
                Sistema de reputación
              </li>
              <li className="flex items-center gap-1.5">
                <Shield className="h-3.5 w-3.5 text-emerald-400" />
                Moderación transparente
              </li>
            </ul>
          </div>

          <div>
            <h4 className="text-xs font-bold text-[#f5f5f5] uppercase tracking-wider mb-3">
              NexoHub
            </h4>
            <p className="text-xs text-[#a0a0a0] leading-relaxed">
              Reseñas confiables creadas por y para verdaderos cinéfilos.
            </p>
          </div>
        </div>

        <div className="pt-6 border-t border-[#0f3460] flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#a0a0a0]">
          <p>&copy; {new Date().getFullYear()} NexoHub. Todos los derechos reservados.</p>
          {movieSource === 'tmdb' ? (
            <p className="text-center sm:text-right">
              Datos e imágenes de películas:{' '}
              <a
                href="https://www.themoviedb.org"
                target="_blank"
                rel="noopener noreferrer"
                className="font-semibold text-[#01b4e4] hover:underline"
              >
                TMDB
              </a>
              . Este producto usa la API de TMDB pero no está avalado ni certificado por TMDB.
            </p>
          ) : (
            <p className="text-center sm:text-right">
              Datos de películas: Cinemeta (IMDb). Sinopsis traducidas con MyMemory.
            </p>
          )}
        </div>
      </div>
    </footer>
  );
}
