import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Award, Medal, Trophy } from 'lucide-react';
import { GENRES, type RankingUserDto } from '@nexohub/shared';
import { Badge, Combobox, Skeleton } from '../components/ui';
import { apiClient } from '../lib/api-client';

export function RankingPage() {
  const [selectedGenre, setSelectedGenre] = useState('');

  const genreOptions = [
    { value: '', label: 'Ranking Global (Todos los géneros)' },
    ...GENRES.map((g) => ({ value: g.slug, label: `Especialistas en ${g.name}` })),
  ];

  const { data: ranking, isLoading } = useQuery({
    queryKey: ['ranking', selectedGenre],
    queryFn: async () => {
      const url = selectedGenre ? `/ranking?genre=${selectedGenre}` : '/ranking';
      const res = await apiClient<{ data: RankingUserDto[] }>(url);
      return res.data;
    },
  });

  return (
    <div className="space-y-8 pb-16">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#0f3460]/60 pb-6">
        <div>
          <div className="flex items-center gap-2">
            <Trophy className="h-6 w-6 text-amber-400" />
            <h1 className="text-2xl sm:text-3xl font-black text-[#f5f5f5]">Tabla de Líderes</h1>
          </div>
          <p className="text-xs sm:text-sm text-[#a0a0a0] mt-1">
            Los críticos más valorados por la comunidad según los me gusta recibidos en sus reseñas.
          </p>
        </div>

        <div className="w-full sm:w-72">
          <Combobox
            options={genreOptions}
            value={selectedGenre}
            onChange={setSelectedGenre}
            placeholder="Filtrar por género..."
            clearable={false}
          />
        </div>
      </div>

      {isLoading ? (
        <div className="space-y-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <Skeleton key={i} className="h-16 w-full rounded-2xl" />
          ))}
        </div>
      ) : ranking && ranking.length > 0 ? (
        <div className="space-y-6">
          {/* Top 3 Podium Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {ranking.slice(0, 3).map((item, index) => {
              const placeIcons = [
                <Trophy key="1" className="h-6 w-6 text-amber-400" />,
                <Medal key="2" className="h-6 w-6 text-[#cbd5e1]" />,
                <Award key="3" className="h-6 w-6 text-amber-600" />,
              ];

              const borders = [
                'border-amber-500/50 bg-[#16213e] shadow-amber-500/10 shadow-lg',
                'border-[#0f3460] bg-[#16213e]',
                'border-amber-800/40 bg-[#16213e]',
              ];

              return (
                <Link
                  key={item.id}
                  to={`/u/${item.username}`}
                  className={`p-6 rounded-2xl border ${borders[index]} flex flex-col items-center text-center space-y-3 group hover:scale-[1.02] transition-transform`}
                >
                  <div className="flex items-center justify-center p-3 rounded-2xl bg-[#0f172a] border border-[#0f3460] shadow-md">
                    {placeIcons[index]}
                  </div>

                  <div className="h-16 w-16 rounded-2xl bg-[#0f3460] border-2 border-white/10 flex items-center justify-center font-bold text-xl text-white overflow-hidden shadow-lg">
                    {item.avatarUrl ? (
                      <img src={item.avatarUrl} alt={item.name} className="h-full w-full object-cover" />
                    ) : (
                      item.name.charAt(0).toUpperCase()
                    )}
                  </div>

                  <div>
                    <h3 className="font-bold text-sm text-[#f5f5f5] group-hover:text-[#e94560] transition-colors">
                      {item.name}
                    </h3>
                    <p className="text-xs text-[#a0a0a0]">@{item.username}</p>
                  </div>

                  <Badge variant={index === 0 ? 'amber' : 'zinc'} size="md">
                    {item.points !== undefined ? `${item.points} pts en género` : `${item.reputation} pts reputación`}
                  </Badge>
                </Link>
              );
            })}
          </div>

          {/* Full List Table */}
          <div className="rounded-2xl bg-[#16213e] border border-[#0f3460] overflow-hidden shadow-xl">
            <div className="divide-y divide-[#0f3460]/60">
              {ranking.map((item, idx) => (
                <Link
                  key={item.id}
                  to={`/u/${item.username}`}
                  className="p-4 flex items-center justify-between gap-4 hover:bg-[#0f172a]/50 transition-colors group"
                >
                  <div className="flex items-center gap-4">
                    <span className="w-6 text-center font-bold text-sm text-[#a0a0a0]">
                      #{idx + 1}
                    </span>

                    <div className="h-10 w-10 rounded-xl bg-[#0f3460] border border-white/10 flex items-center justify-center font-bold text-sm text-white overflow-hidden">
                      {item.avatarUrl ? (
                        <img src={item.avatarUrl} alt={item.name} className="h-full w-full object-cover" />
                      ) : (
                        item.name.charAt(0).toUpperCase()
                      )}
                    </div>

                    <div>
                      <h4 className="font-semibold text-sm text-[#f5f5f5] group-hover:text-[#e94560] transition-colors">
                        {item.name}
                      </h4>
                      <p className="text-xs text-[#a0a0a0]">@{item.username}</p>
                    </div>
                  </div>

                  <div className="text-right">
                    <span className="font-black text-sm text-amber-400">
                      {item.points !== undefined ? item.points : item.reputation}
                    </span>
                    <span className="text-[11px] text-[#a0a0a0] block font-medium">
                      {item.points !== undefined ? 'puntos en categoría' : 'puntos reputación'}
                    </span>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </div>
      ) : (
        <div className="py-20 text-center rounded-2xl bg-[#16213e] border border-[#0f3460] space-y-2">
          <Trophy className="h-10 w-10 text-[#a0a0a0] mx-auto opacity-40" />
          <h3 className="text-base font-semibold text-[#f5f5f5]">Sin registros en esta categoría</h3>
          <p className="text-xs text-[#a0a0a0]">
            Sé el primero en escribir reseñas y recibir me gusta para aparecer en el ranking.
          </p>
        </div>
      )}
    </div>
  );
}
