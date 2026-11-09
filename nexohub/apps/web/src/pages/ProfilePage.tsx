import { useState } from 'react';
import { Navigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Edit3, Film, Heart, Lock, Trophy, User } from 'lucide-react';
import { toast } from 'sonner';
import type { MovieDto, PublicUser } from '@nexohub/shared';
import { Badge, Button, Dialog, Input, Skeleton } from '../components/ui';
import { MovieCard } from '../features/movies/MovieCard';
import { apiClient } from '../lib/api-client';
import { useAuthStore } from '../stores/auth.store';

interface ProfileData {
  user: PublicUser;
  genreBadges: { genre: string; slug: string; points: number }[];
  favorites: MovieDto[];
  isPrivate: boolean;
}

export function ProfilePage() {
  const { username } = useParams<{ username: string }>();
  const queryClient = useQueryClient();
  const { user: currentUser, setUser } = useAuthStore();

  const isOwnProfile = currentUser && currentUser.username.toLowerCase() === username?.toLowerCase();

  if (isOwnProfile) {
    return <Navigate to="/perfil" replace />;
  }

  const [editModalOpen, setEditModalOpen] = useState(false);
  const [editName, setEditName] = useState('');
  const [editBio, setEditBio] = useState('');
  const [editIsPublic, setEditIsPublic] = useState(true);

  const { data, isLoading } = useQuery({
    queryKey: ['profile', username],
    queryFn: async () => {
      const res = await apiClient<{ data: ProfileData }>(`/users/profile/${username}`);
      return res.data;
    },
    enabled: Boolean(username),
  });

  const updateProfileMutation = useMutation({
    mutationFn: async () => {
      return apiClient<{ data: { user: any } }>('/users/me', {
        method: 'PATCH',
        body: JSON.stringify({
          name: editName,
          bio: editBio,
          isPublic: editIsPublic,
        }),
      });
    },
    onSuccess: (res) => {
      toast.success('Perfil actualizado correctamente');
      setUser(res.data.user);
      queryClient.invalidateQueries({ queryKey: ['profile', username] });
      setEditModalOpen(false);
    },
    onError: (err: any) => {
      toast.error(err.message || 'No se pudo actualizar el perfil');
    },
  });

  function openEditModal() {
    if (!currentUser) return;
    setEditName(currentUser.name);
    setEditBio(currentUser.bio || '');
    setEditIsPublic(currentUser.isPublic);
    setEditModalOpen(true);
  }

  if (isLoading) {
    return (
      <div className="space-y-6 pb-16">
        <Skeleton className="h-48 w-full rounded-3xl" />
        <Skeleton className="h-64 w-full rounded-2xl" />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="py-20 text-center space-y-3">
        <User className="h-12 w-12 text-[#a0a0a0] mx-auto opacity-40" />
        <h3 className="text-lg font-bold text-[#f5f5f5]">Usuario no encontrado</h3>
      </div>
    );
  }

  const { user, genreBadges, favorites, isPrivate } = data;

  return (
    <div className="space-y-8 pb-16">
      {/* Profile Card Header */}
      <div className="p-6 sm:p-10 rounded-3xl bg-[#16213e] border border-[#0f3460] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 shadow-2xl">
        <div className="flex items-center gap-5">
          <div className="h-20 w-20 rounded-2xl bg-[#0f3460] border-2 border-white/10 flex items-center justify-center font-black text-3xl text-white overflow-hidden shadow-xl">
            {user.avatarUrl ? (
              <img src={user.avatarUrl} alt={user.name} className="h-full w-full object-cover" />
            ) : (
              user.name.charAt(0).toUpperCase()
            )}
          </div>

          <div className="space-y-1">
            <div className="flex items-center gap-2.5">
              <h1 className="text-xl sm:text-2xl font-black text-[#f5f5f5]">{user.name}</h1>
              <Badge variant={user.role === 'SUPERADMIN' ? 'purple' : user.role === 'ADMIN' ? 'amber' : 'zinc'} size="sm">
                {user.role}
              </Badge>
            </div>
            <p className="text-xs text-[#a0a0a0]">@{user.username}</p>
            {user.bio && <p className="text-xs text-[#cbd5e1] max-w-lg mt-2">{user.bio}</p>}
          </div>
        </div>

        <div className="flex items-center gap-3">
          <div className="p-4 rounded-2xl bg-[#0f172a] border border-[#0f3460] text-center min-w-28 shadow-lg">
            <div className="flex items-center justify-center gap-1.5 text-amber-400">
              <Trophy className="h-4 w-4" />
              <span className="font-black text-xl">{user.reputation}</span>
            </div>
            <p className="text-[10px] uppercase font-bold tracking-wider text-[#a0a0a0] mt-0.5">
              Reputación
            </p>
          </div>

          {isOwnProfile && (
            <Button variant="secondary" onClick={openEditModal} leftIcon={<Edit3 className="h-4 w-4" />}>
              Editar
            </Button>
          )}
        </div>
      </div>

      {/* Genre Reputation Badges */}
      {genreBadges.length > 0 && (
        <section className="space-y-4">
          <h2 className="text-sm font-bold uppercase tracking-wider text-[#a0a0a0]">
            Especialidad por Género
          </h2>
          <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
            {genreBadges.map((badge) => (
              <div
                key={badge.slug}
                className="p-3.5 rounded-2xl bg-[#16213e] border border-[#0f3460] text-center space-y-1 shadow-md"
              >
                <p className="text-xs font-semibold text-[#f5f5f5]">{badge.genre}</p>
                <p className="text-xs font-black text-amber-400">{badge.points} pts</p>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Favorites Section */}
      <section className="space-y-4">
        <div className="flex items-center gap-2 border-b border-[#0f3460]/60 pb-3">
          <Heart className="h-4 w-4 text-[#e94560] fill-[#e94560]" />
          <h2 className="text-base font-bold text-[#f5f5f5]">
            Películas Favoritas ({favorites.length})
          </h2>
        </div>

        {isPrivate && !isOwnProfile ? (
          <div className="py-12 text-center rounded-2xl bg-[#16213e] border border-[#0f3460] space-y-2">
            <Lock className="h-8 w-8 text-[#a0a0a0] mx-auto opacity-50" />
            <p className="text-xs text-[#a0a0a0]">Este usuario configuró su perfil como privado.</p>
          </div>
        ) : favorites.length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            {favorites.map((m) => (
              <MovieCard key={m.id} movie={m} />
            ))}
          </div>
        ) : (
          <div className="py-12 text-center rounded-2xl bg-[#16213e] border border-[#0f3460] space-y-2">
            <Film className="h-8 w-8 text-[#a0a0a0] mx-auto opacity-50" />
            <p className="text-xs text-[#a0a0a0]">No hay películas marcadas como favoritas aún.</p>
          </div>
        )}
      </section>

      {/* Edit Profile Modal */}
      <Dialog
        open={editModalOpen}
        onClose={() => setEditModalOpen(false)}
        title="Editar Perfil"
        description="Modifica tus datos públicos visibles en la comunidad de NexoHub."
      >
        <div className="space-y-4">
          <Input
            label="Nombre Completo"
            value={editName}
            onChange={(e) => setEditName(e.target.value)}
          />

          <div className="space-y-1.5">
            <label className="block text-xs font-semibold text-[#f5f5f5]">Biografía</label>
            <textarea
              rows={3}
              value={editBio}
              onChange={(e) => setEditBio(e.target.value)}
              placeholder="Cuéntale a la comunidad tus gustos cinematográficos..."
              className="w-full bg-[#0f172a] border border-[#0f3460] focus:border-[#e94560] rounded-xl px-3.5 py-2.5 text-sm text-[#f5f5f5] placeholder-[#a0a0a0]/60 focus:outline-none transition-colors resize-none"
            />
          </div>

          <div className="flex items-center justify-between p-3.5 rounded-xl bg-[#0f172a] border border-[#0f3460]">
            <div>
              <p className="text-xs font-semibold text-[#f5f5f5]">Perfil Público</p>
              <p className="text-[11px] text-[#a0a0a0]">Permitir que otros usuarios vean tus favoritas</p>
            </div>
            <input
              type="checkbox"
              checked={editIsPublic}
              onChange={(e) => setEditIsPublic(e.target.checked)}
              className="h-4 w-4 rounded accent-[#e94560] cursor-pointer"
            />
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <Button variant="ghost" onClick={() => setEditModalOpen(false)}>
              Cancelar
            </Button>
            <Button
              variant="primary"
              onClick={() => updateProfileMutation.mutate()}
              isLoading={updateProfileMutation.isPending}
            >
              Guardar Cambios
            </Button>
          </div>
        </div>
      </Dialog>
    </div>
  );
}
