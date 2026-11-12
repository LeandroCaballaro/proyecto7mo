import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowUpRight, Crown, Medal, Trophy } from 'lucide-react';
import { GENRES, type RankingUserDto } from '@nexohub/shared';
import { Combobox, Skeleton } from '../components/ui';
import { apiClient } from '../lib/api-client';

export function RankingPage() {
  const [selectedGenre, setSelectedGenre] = useState('');

  const genreOptions = [
    { value: '', label: 'Ranking global' },
    ...GENRES.map((genre) => ({ value: genre.slug, label: `Especialistas en ${genre.name}` })),
  ];

  const { data: ranking, isLoading } = useQuery({
    queryKey: ['ranking', selectedGenre],
    queryFn: async () => {
      const url = selectedGenre ? `/ranking?genre=${selectedGenre}` : '/ranking';
      return (await apiClient<{ data: RankingUserDto[] }>(url)).data;
    },
  });

  const users = ranking ?? [];
  const genreName = GENRES.find((genre) => genre.slug === selectedGenre)?.name;
  const maxScore = Math.max(...users.map((user) => user.points ?? user.reputation), 1);
  const totalScore = users.reduce((total, user) => total + (user.points ?? user.reputation), 0);
  const leader = users[0];
  const leaderScore = leader ? (leader.points ?? leader.reputation) : 0;

  function rankDecoration(index: number) {
    if (index === 0) return 'bg-amber-400/[0.065]';
    if (index === 1) return 'bg-slate-200/[0.035]';
    if (index === 2) return 'bg-orange-400/[0.035]';
    return 'hover:bg-white/[0.035]';
  }

  return (
    <div className="min-h-[calc(100vh-4rem)] bg-[#0b1224] pb-20 sm:pb-24">
      <div className="pointer-events-none fixed inset-0 bg-[radial-gradient(circle_at_78%_12%,rgba(233,69,96,0.08),transparent_30%)]" />

      <div className="relative mx-auto max-w-7xl px-4 pb-8 pt-10 sm:px-6 sm:pb-10 sm:pt-14">
        <header className="flex flex-col gap-7 border-b border-white/10 pb-8 lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-2xl">
            <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-[#ff617a]">
              <Trophy className="h-4 w-4" />
              Ranking NexoHub
            </div>
            <h1 className="mt-3 text-4xl font-black tracking-[-0.035em] text-white sm:text-5xl">
              Las voces que marcan la conversación
            </h1>
            <p className="mt-3 text-sm leading-6 text-white/50">
              Compará posiciones, reputación y especialistas reconocidos por la comunidad.
            </p>
          </div>

          <div className="w-full sm:w-80">
            <Combobox
              options={genreOptions}
              value={selectedGenre}
              onChange={setSelectedGenre}
              placeholder="Ranking global"
              clearable={false}
              searchable={false}
              className="[&_button]:h-11 [&_button]:border-white/15 [&_button]:bg-white/[0.04]"
            />
          </div>
        </header>

        {isLoading ? (
          <div className="mt-8 space-y-2">
            {Array.from({ length: 8 }).map((_, index) => (
              <Skeleton key={index} className="h-[88px] w-full rounded-xl" />
            ))}
          </div>
        ) : users.length > 0 ? (
          <div className="mt-10 overflow-hidden rounded-3xl border border-white/10 bg-[#101a31]/90 p-4 shadow-2xl shadow-black/25 backdrop-blur-sm sm:p-6">
            <div className="relative mb-6 overflow-hidden rounded-2xl border border-amber-300/15 bg-gradient-to-r from-amber-400/[0.09] via-[#16213e] to-[#16213e] p-5 sm:p-6">
              <div className="pointer-events-none absolute -right-12 -top-20 h-56 w-56 rounded-full border-[42px] border-amber-300/[0.035]" />
              <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-4">
                  <div className="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-amber-300/30 bg-[#0f3460] text-xl font-black text-white shadow-xl shadow-black/20">
                    {leader?.avatarUrl ? (
                      <img
                        src={leader.avatarUrl}
                        alt={leader.name}
                        className="h-full w-full object-cover"
                      />
                    ) : (
                      leader?.name.charAt(0).toUpperCase()
                    )}
                    <span className="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-md bg-amber-400 text-[#17120a]">
                      <Crown className="h-3 w-3 fill-current" />
                    </span>
                  </div>
                  <div>
                    <p className="text-[10px] font-black uppercase tracking-[0.18em] text-amber-300/70">
                      Liderando la clasificación
                    </p>
                    <p className="mt-1 text-lg font-black text-white">{leader?.name}</p>
                    <p className="text-xs text-white/40">@{leader?.username}</p>
                  </div>
                </div>

                <div className="flex items-end gap-8">
                  <div>
                    <span className="block text-3xl font-black tabular-nums text-amber-300">
                      {leaderScore}
                    </span>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-white/30">
                      puntos del líder
                    </span>
                  </div>
                  <div>
                    <span className="block text-3xl font-black tabular-nums text-[#ff7187]">
                      {totalScore}
                    </span>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-white/30">
                      puntos totales
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3 px-1">
              <div>
                <p className="text-sm font-bold text-white">
                  Clasificación {genreName ? `de ${genreName}` : 'general'}
                </p>
                <p className="mt-1 text-xs text-white/40">
                  {users.length}{' '}
                  {users.length === 1 ? 'crítico clasificado' : 'críticos clasificados'}
                </p>
              </div>
              <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-white/25">
                Actualizado según votos de la comunidad
              </p>
            </div>

            <div className="overflow-x-auto rounded-2xl border border-white/[0.08] custom-scrollbar">
              <table className="w-full min-w-[840px] border-collapse text-left">
                <thead>
                  <tr className="bg-white/[0.045] text-[10px] font-black uppercase tracking-[0.16em] text-white/35">
                    <th className="w-28 px-6 py-4">Posición</th>
                    <th className="px-6 py-4">Crítico</th>
                    <th className="w-[30%] px-6 py-4">Rendimiento</th>
                    <th className="w-40 px-6 py-4 text-right">Puntuación</th>
                    <th className="w-20 px-6 py-4">
                      <span className="sr-only">Perfil</span>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {users.map((user, index) => {
                    const score = user.points ?? user.reputation;
                    const percentage = Math.max((score / maxScore) * 100, 4);
                    const topIcon =
                      index === 0 ? (
                        <Crown className="h-4 w-4 fill-current" />
                      ) : index < 3 ? (
                        <Medal className="h-4 w-4" />
                      ) : null;

                    return (
                      <tr
                        key={user.id}
                        className={`group border-t border-white/[0.065] transition-colors ${rankDecoration(index)}`}
                      >
                        <td
                          className={`border-l-2 px-6 py-5 ${index === 0 ? 'border-amber-400' : index === 1 ? 'border-slate-300/60' : index === 2 ? 'border-orange-400/60' : 'border-transparent'}`}
                        >
                          <div
                            className={`flex items-center gap-2 text-lg font-black tabular-nums ${
                              index === 0
                                ? 'text-amber-300'
                                : index === 1
                                  ? 'text-slate-300'
                                  : index === 2
                                    ? 'text-orange-300'
                                    : 'text-white/35'
                            }`}
                          >
                            {topIcon}
                            <span>#{index + 1}</span>
                          </div>
                        </td>

                        <td className="px-6 py-5">
                          <Link
                            to={`/u/${user.username}`}
                            className="flex min-w-0 items-center gap-3.5"
                          >
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white/10 bg-gradient-to-br from-[#1b4773] to-[#0f2d50] font-black text-white shadow-lg">
                              {user.avatarUrl ? (
                                <img
                                  src={user.avatarUrl}
                                  alt={user.name}
                                  loading="lazy"
                                  className="h-full w-full object-cover"
                                />
                              ) : (
                                user.name.charAt(0).toUpperCase()
                              )}
                            </div>
                            <div className="min-w-0">
                              <h2 className="truncate text-sm font-bold text-white transition-colors group-hover:text-[#ff7187]">
                                {user.name}
                              </h2>
                              <p className="mt-0.5 truncate text-xs text-white/40">
                                @{user.username}
                              </p>
                            </div>
                          </Link>
                        </td>

                        <td className="px-6 py-5">
                          <div className="h-2 overflow-hidden rounded-full border border-white/[0.04] bg-black/25">
                            <div
                              className={`h-full rounded-full ${index === 0 ? 'bg-amber-400' : 'bg-[#e94560]'}`}
                              style={{ width: `${percentage}%` }}
                            />
                          </div>
                          <div className="mt-2 flex items-center justify-between text-[10px] font-semibold uppercase tracking-wider text-white/30">
                            <span>{percentage.toFixed(0)}% del líder</span>
                            <span>
                              {leaderScore - score === 0 ? 'Líder' : `A ${leaderScore - score} pts`}
                            </span>
                          </div>
                        </td>

                        <td className="px-6 py-5 text-right">
                          <span
                            className={`text-xl font-black tabular-nums ${index === 0 ? 'text-amber-300' : 'text-white'}`}
                          >
                            {score}
                          </span>
                          <span className="ml-1 text-[10px] font-bold uppercase text-white/30">
                            pts
                          </span>
                          <p className="mt-1 text-[10px] text-white/35">
                            {user.points !== undefined ? 'en especialidad' : 'reputación'}
                          </p>
                        </td>

                        <td className="px-6 py-5">
                          <Link
                            to={`/u/${user.username}`}
                            className="flex h-9 w-9 items-center justify-center rounded-full border border-white/10 text-white/30 transition-all group-hover:border-[#e94560]/50 group-hover:bg-[#e94560] group-hover:text-white"
                            aria-label={`Ver perfil de ${user.name}`}
                          >
                            <ArrowUpRight className="h-4 w-4" />
                          </Link>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        ) : (
          <div className="mt-8 flex min-h-[420px] items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/[0.02] px-4 text-center">
            <div>
              <Trophy className="mx-auto h-9 w-9 text-white/20" />
              <h2 className="mt-4 text-xl font-black text-white">Todavía no hay posiciones</h2>
              <p className="mt-2 text-sm text-white/40">
                Sé el primero en recibir reconocimiento por tus reseñas.
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
