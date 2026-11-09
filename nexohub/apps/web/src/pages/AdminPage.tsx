import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Check, Clock, Film, Shield, ShieldCheck, Users, X } from 'lucide-react';
import { toast } from 'sonner';
import { ROLES, type AuthUser, type MovieDto, type Role } from '@nexohub/shared';
import { Badge, Button, Combobox, Dialog, Skeleton } from '../components/ui';
import { apiClient } from '../lib/api-client';
import { useAuthStore } from '../stores/auth.store';

export function AdminPage() {
  const queryClient = useQueryClient();
  const { user: currentUser } = useAuthStore();
  const [activeTab, setActiveTab] = useState<'pending' | 'users'>('pending');

  // Estado para rechazo
  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [rejectingMovieId, setRejectingMovieId] = useState<number | null>(null);
  const [rejectReason, setRejectReason] = useState('');

  // 1. Películas pendientes
  const { data: pendingMovies, isLoading: isPendingLoading } = useQuery({
    queryKey: ['admin', 'pending-movies'],
    queryFn: async () => {
      const res = await apiClient<{ data: MovieDto[] }>('/admin/movies/pending');
      return res.data;
    },
  });

  // 2. Usuarios
  const { data: users, isLoading: isUsersLoading } = useQuery({
    queryKey: ['admin', 'users'],
    queryFn: async () => {
      const res = await apiClient<{ data: AuthUser[] }>('/admin/users');
      return res.data;
    },
  });

  // Aprobar película
  const approveMutation = useMutation({
    mutationFn: async (movieId: number) => {
      return apiClient(`/admin/movies/${movieId}/approve`, { method: 'PATCH' });
    },
    onSuccess: () => {
      toast.success('Película aprobada e incorporada al catálogo');
      queryClient.invalidateQueries({ queryKey: ['admin', 'pending-movies'] });
      queryClient.invalidateQueries({ queryKey: ['movies'] });
    },
    onError: () => toast.error('Error al aprobar la película'),
  });

  // Rechazar película
  const rejectMutation = useMutation({
    mutationFn: async ({ movieId, reason }: { movieId: number; reason: string }) => {
      return apiClient(`/admin/movies/${movieId}/reject`, {
        method: 'PATCH',
        body: JSON.stringify({ reason }),
      });
    },
    onSuccess: () => {
      toast.success('Película rechazada con motivo registrado');
      setRejectModalOpen(false);
      setRejectReason('');
      setRejectingMovieId(null);
      queryClient.invalidateQueries({ queryKey: ['admin', 'pending-movies'] });
    },
    onError: () => toast.error('Error al rechazar la película'),
  });

  // Modificar rol de usuario (solo SUPERADMIN)
  const updateRoleMutation = useMutation({
    mutationFn: async ({ userId, role }: { userId: number; role: Role }) => {
      return apiClient(`/admin/users/${userId}/role`, {
        method: 'PATCH',
        body: JSON.stringify({ role }),
      });
    },
    onSuccess: () => {
      toast.success('Rol de usuario actualizado');
      queryClient.invalidateQueries({ queryKey: ['admin', 'users'] });
    },
    onError: (err: any) => toast.error(err.message || 'No se pudo actualizar el rol'),
  });

  const isSuperadmin = currentUser?.role === 'SUPERADMIN';

  return (
    <div className="space-y-8 pb-16">
      {/* Header */}
      <div className="border-b border-[#0f3460]/60 pb-6">
        <div className="flex items-center gap-2">
          <ShieldCheck className="h-6 w-6 text-amber-400" />
          <h1 className="text-2xl sm:text-3xl font-black text-[#f5f5f5]">Panel de Administración</h1>
        </div>
        <p className="text-xs sm:text-sm text-[#a0a0a0] mt-1">
          Modera el catálogo y gestiona los permisos de los miembros de NexoHub.
        </p>

        {/* Tab Buttons */}
        <div className="flex items-center gap-2 mt-6">
          <Button
            variant={activeTab === 'pending' ? 'primary' : 'outline'}
            size="sm"
            onClick={() => setActiveTab('pending')}
            leftIcon={<Clock className="h-4 w-4" />}
          >
            Películas Pendientes ({pendingMovies?.length ?? 0})
          </Button>
          <Button
            variant={activeTab === 'users' ? 'primary' : 'outline'}
            size="sm"
            onClick={() => setActiveTab('users')}
            leftIcon={<Users className="h-4 w-4" />}
          >
            Usuarios Registrados ({users?.length ?? 0})
          </Button>
        </div>
      </div>

      {/* Tab: Películas Pendientes */}
      {activeTab === 'pending' && (
        <section className="space-y-4">
          {isPendingLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 3 }).map((_, i) => (
                <Skeleton key={i} className="h-28 w-full rounded-2xl" />
              ))}
            </div>
          ) : pendingMovies && pendingMovies.length > 0 ? (
            <div className="space-y-3">
              {pendingMovies.map((movie) => (
                <div
                  key={movie.id}
                  className="p-5 rounded-2xl bg-[#16213e] border border-[#0f3460] flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-lg"
                >
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <h3 className="font-bold text-base text-[#f5f5f5]">{movie.title}</h3>
                      <Badge variant="rose" size="sm">
                        {movie.genre.name}
                      </Badge>
                      {movie.releaseYear && (
                        <span className="text-xs text-[#a0a0a0]">({movie.releaseYear})</span>
                      )}
                    </div>
                    {movie.director && (
                      <p className="text-xs text-[#a0a0a0]">Director: {movie.director}</p>
                    )}
                    {movie.synopsis && (
                      <p className="text-xs text-[#cbd5e1] max-w-xl line-clamp-2 mt-1">
                        {movie.synopsis}
                      </p>
                    )}
                    {movie.proposedBy && (
                      <p className="text-[11px] text-[#a0a0a0] pt-1">
                        Propuesta por: @{movie.proposedBy.username} ({movie.proposedBy.name})
                      </p>
                    )}
                  </div>

                  <div className="flex items-center gap-2 shrink-0">
                    <Button
                      variant="primary"
                      size="sm"
                      onClick={() => approveMutation.mutate(movie.id)}
                      isLoading={approveMutation.isPending}
                      leftIcon={<Check className="h-4 w-4" />}
                    >
                      Aprobar
                    </Button>
                    <Button
                      variant="danger"
                      size="sm"
                      onClick={() => {
                        setRejectingMovieId(movie.id);
                        setRejectModalOpen(true);
                      }}
                      leftIcon={<X className="h-4 w-4" />}
                    >
                      Rechazar
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="py-16 text-center rounded-2xl bg-[#16213e] border border-[#0f3460] space-y-2">
              <Check className="h-8 w-8 text-emerald-400 mx-auto" />
              <h3 className="text-base font-semibold text-[#f5f5f5]">Todo al día</h3>
              <p className="text-xs text-[#a0a0a0]">No hay propuestas pendientes de revisión.</p>
            </div>
          )}
        </section>
      )}

      {/* Tab: Gestión de Usuarios */}
      {activeTab === 'users' && (
        <section className="space-y-4">
          {isUsersLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, i) => (
                <Skeleton key={i} className="h-16 w-full rounded-2xl" />
              ))}
            </div>
          ) : users && users.length > 0 ? (
            <div className="rounded-2xl bg-[#16213e] border border-[#0f3460] overflow-hidden divide-y divide-[#0f3460]/60 shadow-xl">
              {users.map((u) => {
                const roleOptions = ROLES.map((r) => ({ value: r, label: r }));

                return (
                  <div key={u.id} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                      <div className="h-10 w-10 rounded-xl bg-[#0f3460] border border-white/10 flex items-center justify-center font-bold text-xs text-white">
                        {u.name.charAt(0).toUpperCase()}
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                          <h4 className="font-semibold text-sm text-[#f5f5f5]">{u.name}</h4>
                          <Badge variant={u.role === 'SUPERADMIN' ? 'purple' : u.role === 'ADMIN' ? 'amber' : 'zinc'} size="sm">
                            {u.role}
                          </Badge>
                        </div>
                        <p className="text-xs text-[#a0a0a0]">
                          @{u.username} • {u.email}
                        </p>
                      </div>
                    </div>

                    {isSuperadmin && u.id !== currentUser?.id ? (
                      <div className="w-44">
                        <Combobox
                          options={roleOptions}
                          value={u.role}
                          onChange={(newRole) => {
                            if (newRole) {
                              updateRoleMutation.mutate({ userId: u.id, role: newRole as Role });
                            }
                          }}
                          clearable={false}
                          searchable={false}
                          size="sm"
                        />
                      </div>
                    ) : (
                      <span className="text-xs text-[#a0a0a0] font-medium">
                        {u.id === currentUser?.id ? 'Tu cuenta' : 'Protegido'}
                      </span>
                    )}
                  </div>
                );
              })}
            </div>
          ) : null}
        </section>
      )}

      {/* Reject Reason Dialog */}
      <Dialog
        open={rejectModalOpen}
        onClose={() => {
          setRejectModalOpen(false);
          setRejectReason('');
          setRejectingMovieId(null);
        }}
        title="Rechazar propuesta de película"
        description="Ingresa el motivo del rechazo para que el usuario pueda corregirlo si corresponde."
      >
        <div className="space-y-4">
          <textarea
            value={rejectReason}
            onChange={(e) => setRejectReason(e.target.value)}
            placeholder="Motivo del rechazo (ej. Datos incompletos, póster de baja resolución, película duplicada)..."
            rows={3}
            className="w-full bg-[#0f172a] border border-[#0f3460] focus:border-[#e94560] rounded-xl p-3 text-sm text-white placeholder-[#a0a0a0]/60 outline-none transition-colors"
          />

          <div className="flex justify-end gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                setRejectModalOpen(false);
                setRejectReason('');
                setRejectingMovieId(null);
              }}
            >
              Cancelar
            </Button>
            <Button
              variant="danger"
              size="sm"
              onClick={() => {
                if (rejectingMovieId) {
                  rejectMutation.mutate({ movieId: rejectingMovieId, reason: rejectReason });
                }
              }}
              isLoading={rejectMutation.isPending}
            >
              Confirmar Rechazo
            </Button>
          </div>
        </div>
      </Dialog>
    </div>
  );
}
