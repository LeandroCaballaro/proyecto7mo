import { useState, useRef, useEffect } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Camera,
  Check,
  Clock,
  Film,
  Globe,
  Heart,
  Home,
  Lock,
  LogOut,
  MessageSquare,
  Pencil,
  PlusCircle,
  Settings,
  Shield,
  Star,
  Trash2,
  Upload,
  User as UserIcon,
  X,
} from 'lucide-react';
import { toast } from 'sonner';
import { Badge, Button, ConfirmDialog, Dialog, Input, Skeleton, StarRating } from '../components/ui';
import { apiClient } from '../lib/api-client';
import { cn } from '../lib/cn';
import { useAuthStore } from '../stores/auth.store';

type DashboardSection = 'perfil' | 'resenas' | 'favoritas' | 'actividad' | 'config';

interface DashboardData {
  user: {
    id: number;
    name: string;
    username: string;
    email: string;
    avatarUrl: string | null;
    bio: string | null;
    isPublic: boolean;
    role: 'USER' | 'ADMIN' | 'SUPERADMIN';
    reputation: number;
  };
  commentsCount: number;
  reviewsCount: number;
  reputation: number;
  reviews: Array<{
    id: number;
    movieId: number;
    title: string;
    posterUrl: string | null;
    rating: number;
    content: string | null;
    createdAt: string;
  }>;
  favorites: Array<{
    id: number;
    title: string;
    posterUrl: string | null;
    ratingAvg: number;
  }>;
  activity: Array<{
    id: string;
    type: 'review' | 'response';
    movieId: number;
    movieTitle: string;
    comment: string;
    rating?: number;
    createdAt: string;
  }>;
}

export function UserProfileDashboard() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { user: authUser, setUser, logout } = useAuthStore();
  const [searchParams, setSearchParams] = useSearchParams();

  const initialTab = (searchParams.get('tab') as DashboardSection) || 'perfil';
  const [activeSection, setActiveSection] = useState<DashboardSection>(initialTab);

  // Estados de edición de descripción
  const [isEditingBio, setIsEditingBio] = useState(false);
  const [bioInput, setBioInput] = useState('');

  // Estados de modal de edición de datos (Nombre / Username)
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [editName, setEditName] = useState('');
  const [editUsername, setEditUsername] = useState('');

  // Estados para foto de perfil
  const [photoModalOpen, setPhotoModalOpen] = useState(false);
  const [photoUrlInput, setPhotoUrlInput] = useState('');
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Estado para modal de confirmación de eliminar cuenta
  const [deleteAccountModalOpen, setDeleteAccountModalOpen] = useState(false);

  // Fetch de información del dashboard
  const { data: dashboard, isLoading } = useQuery({
    queryKey: ['my-dashboard'],
    queryFn: async () => {
      const res = await apiClient<{ data: DashboardData }>('/users/me/dashboard');
      return res.data;
    },
    enabled: Boolean(authUser),
  });

  useEffect(() => {
    if (dashboard?.user) {
      setBioInput(dashboard.user.bio || '');
      setEditName(dashboard.user.name);
      setEditUsername(dashboard.user.username);
    }
  }, [dashboard]);

  // Mutación para actualizar perfil (nombre, username, bio, isPublic)
  const updateProfileMutation = useMutation({
    mutationFn: async (payload: {
      name?: string;
      username?: string;
      bio?: string;
      avatarUrl?: string | null;
      isPublic?: boolean;
    }) => {
      return apiClient<{ data: { user: any } }>('/users/me', {
        method: 'PATCH',
        body: JSON.stringify(payload),
      });
    },
    onSuccess: (res) => {
      toast.success('Perfil actualizado correctamente');
      setUser(res.data.user);
      queryClient.invalidateQueries({ queryKey: ['my-dashboard'] });
      setIsEditingBio(false);
      setEditModalOpen(false);
      setPhotoModalOpen(false);
    },
    onError: (err: any) => {
      toast.error(err.message || 'Error al actualizar el perfil');
    },
  });

  // Mutación para eliminar cuenta permanentemente
  const deleteAccountMutation = useMutation({
    mutationFn: async () => {
      return apiClient('/users/me', { method: 'DELETE' });
    },
    onSuccess: () => {
      logout();
      toast.success('Tu cuenta ha sido eliminada permanentemente');
      navigate('/');
    },
    onError: (err: any) => {
      toast.error(err.message || 'No se pudo eliminar la cuenta');
    },
  });

  // Manejo de carga de archivo de imagen
  function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      toast.error('Por favor selecciona un archivo de imagen válido');
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      toast.error('La imagen no puede superar los 5MB');
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      const base64 = reader.result as string;
      updateProfileMutation.mutate({ avatarUrl: base64 });
    };
    reader.readAsDataURL(file);
  }

  const currentUser = dashboard?.user || authUser;
  const userInitial = (currentUser?.name || 'U').charAt(0).toUpperCase();

  const fallbackPoster =
    'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="240" height="360" viewBox="0 0 240 360"><rect width="240" height="360" fill="%231a1a2e"/><circle cx="120" cy="150" r="42" fill="%23e94560" opacity="0.35"/><text x="120" y="230" font-family="Arial,sans-serif" font-size="20" fill="%23f5f5f5" text-anchor="middle">NexoHub</text></svg>';

  if (!authUser) {
    return (
      <div className="py-20 text-center space-y-4">
        <UserIcon className="h-12 w-12 text-[#a0a0a0] mx-auto opacity-50" />
        <h2 className="text-xl font-bold text-white">Debes iniciar sesión</h2>
        <p className="text-xs text-[#a0a0a0]">Ingresa a tu cuenta para acceder a tu perfil y configuración.</p>
        <Link to="/login">
          <Button variant="primary">Iniciar Sesión</Button>
        </Link>
      </div>
    );
  }

  return (
    <div className="w-full h-screen flex flex-col md:flex-row bg-[#1a1a2e] text-[#f5f5f5] overflow-hidden">
      {/* ========================================================= */}
      {/* BARRA LATERAL (SIDEBAR) EXACTA A LA FOTO DE REFERENCIA    */}
      {/* ========================================================= */}
      <aside className="w-full md:w-64 lg:w-72 h-full bg-[#1a1a2e] border-r border-white/[0.08] p-6 flex flex-col justify-between shrink-0 overflow-y-auto">
        <div className="space-y-8">
          {/* Logo NexoHub */}
          <Link to="/" className="flex items-center gap-3 px-2 group">
            <div className="h-10 w-10 rounded-xl bg-[#e94560] flex items-center justify-center text-white shadow-lg shadow-[#e94560]/30 transition-transform group-hover:scale-105">
              <svg fill="currentColor" viewBox="0 0 24 24" className="h-5 w-5 ml-0.5">
                <path d="M7 4v16l13-8L7 4z" />
              </svg>
            </div>
            <span className="text-xl font-black tracking-tight text-white">
              Nexo<span className="text-[#e94560]">Hub</span>
            </span>
          </Link>

          {/* Menú de Navegación Vertical */}
          <nav className="space-y-1">
            {/* 1. Mi perfil */}
            <button
              type="button"
              onClick={() => {
                setActiveSection('perfil');
                setSearchParams({ tab: 'perfil' });
              }}
              className={cn(
                'w-full flex items-center gap-3.5 px-4 py-2.5 rounded-full text-[0.925rem] transition-all duration-200 cursor-pointer',
                activeSection === 'perfil'
                  ? 'bg-[rgba(233,69,96,0.12)] text-white font-semibold border border-[rgba(233,69,96,0.2)]'
                  : 'text-[#f5f5f5] hover:bg-white/[0.04] border border-transparent font-medium',
              )}
            >
              <div className="h-8 w-8 rounded-full bg-[rgba(66,133,244,0.18)] text-[#4285f4] flex items-center justify-center shrink-0">
                <UserIcon className="h-4 w-4" />
              </div>
              <span>Mi perfil</span>
            </button>

            {/* 2. Mis reseñas */}
            <button
              type="button"
              onClick={() => {
                setActiveSection('resenas');
                setSearchParams({ tab: 'resenas' });
              }}
              className={cn(
                'w-full flex items-center gap-3.5 px-4 py-2.5 rounded-full text-[0.925rem] transition-all duration-200 cursor-pointer',
                activeSection === 'resenas'
                  ? 'bg-[rgba(233,69,96,0.12)] text-white font-semibold border border-[rgba(233,69,96,0.2)]'
                  : 'text-[#f5f5f5] hover:bg-white/[0.04] border border-transparent font-medium',
              )}
            >
              <div className="h-8 w-8 rounded-full bg-[rgba(52,168,83,0.18)] text-[#34a853] flex items-center justify-center shrink-0">
                <MessageSquare className="h-4 w-4" />
              </div>
              <span>Mis reseñas</span>
            </button>

            {/* 3. Películas favoritas */}
            <button
              type="button"
              onClick={() => {
                setActiveSection('favoritas');
                setSearchParams({ tab: 'favoritas' });
              }}
              className={cn(
                'w-full flex items-center gap-3.5 px-4 py-2.5 rounded-full text-[0.925rem] transition-all duration-200 cursor-pointer',
                activeSection === 'favoritas'
                  ? 'bg-[rgba(233,69,96,0.12)] text-white font-semibold border border-[rgba(233,69,96,0.2)]'
                  : 'text-[#f5f5f5] hover:bg-white/[0.04] border border-transparent font-medium',
              )}
            >
              <div className="h-8 w-8 rounded-full bg-[rgba(224,63,142,0.18)] text-[#e03f8e] flex items-center justify-center shrink-0">
                <Star className="h-4 w-4 fill-current" />
              </div>
              <span>Peliculas favoritas</span>
            </button>

            {/* 4. Mi actividad */}
            <button
              type="button"
              onClick={() => {
                setActiveSection('actividad');
                setSearchParams({ tab: 'actividad' });
              }}
              className={cn(
                'w-full flex items-center gap-3.5 px-4 py-2.5 rounded-full text-[0.925rem] transition-all duration-200 cursor-pointer',
                activeSection === 'actividad'
                  ? 'bg-[rgba(233,69,96,0.12)] text-white font-semibold border border-[rgba(233,69,96,0.2)]'
                  : 'text-[#f5f5f5] hover:bg-white/[0.04] border border-transparent font-medium',
              )}
            >
              <div className="h-8 w-8 rounded-full bg-[rgba(26,115,232,0.18)] text-[#1a73e8] flex items-center justify-center shrink-0">
                <Globe className="h-4 w-4" />
              </div>
              <span>Mi actividad</span>
            </button>

            {/* 5. Configuración */}
            <button
              type="button"
              onClick={() => {
                setActiveSection('config');
                setSearchParams({ tab: 'config' });
              }}
              className={cn(
                'w-full flex items-center gap-3.5 px-4 py-2.5 rounded-full text-[0.925rem] transition-all duration-200 cursor-pointer',
                activeSection === 'config'
                  ? 'bg-[rgba(233,69,96,0.12)] text-white font-semibold border border-[rgba(233,69,96,0.2)]'
                  : 'text-[#f5f5f5] hover:bg-white/[0.04] border border-transparent font-medium',
              )}
            >
              <div className="h-8 w-8 rounded-full bg-[rgba(161,79,243,0.18)] text-[#a14ff3] flex items-center justify-center shrink-0">
                <Settings className="h-4 w-4" />
              </div>
              <span>Configuración</span>
            </button>
          </nav>
        </div>

        {/* Botón Cerrar Sesión inferior */}
        <div className="pt-6 border-t border-[#0f3460]/60">
          <button
            type="button"
            onClick={logout}
            className="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-[#a0a0a0] hover:text-red-400 hover:bg-red-500/10 transition-colors cursor-pointer"
          >
            <LogOut className="h-4 w-4" />
            <span>Cerrar Sesión</span>
          </button>
        </div>
      </aside>

      {/* ========================================================= */}
      {/* CONTENIDO PRINCIPAL DERECHA                               */}
      {/* ========================================================= */}
      <main className="flex-1 h-full flex flex-col min-w-0 bg-[#1a1a2e] overflow-hidden">
        {/* Barra superior del contenido */}
        <header className="h-16 border-b border-white/[0.08] px-6 sm:px-10 flex items-center justify-between shrink-0 bg-[#1a1a2e] z-10">
          <span className="text-sm font-bold text-[#f5f5f5] tracking-wide">
            NexoHub Perfil
          </span>

          <Link
            to="/"
            className="min-h-[38px] px-4 py-1.5 rounded-full border border-white/10 bg-[#16213e] hover:border-[#e94560] hover:text-[#e94560] text-sm font-bold text-white inline-flex items-center gap-2 transition-all shadow-sm"
            title="Volver al inicio"
          >
            <Home className="h-4 w-4 text-[#e94560]" />
            <span>Home</span>
          </Link>
        </header>

        {/* Cuerpo de la Sección Central */}
        <div className="flex-1 p-6 sm:p-10 overflow-y-auto overflow-x-hidden">
          {/* ========================================================= */}
          {/* TAB 1: MI PERFIL (EXACTO A LA FOTO DE REFERENCIA)         */}
          {/* ========================================================= */}
          {activeSection === 'perfil' && (
            <div className="max-w-2xl mx-auto flex flex-col items-center text-center pt-8 pb-12 animate-in fade-in-50 duration-200">
              {/* Avatar Naranja Grande con Botón de Foto */}
              <div className="relative mb-6">
                <div
                  className="w-[110px] h-[110px] rounded-full flex items-center justify-center text-[3.2rem] font-medium text-white shadow-2xl overflow-hidden bg-cover bg-center"
                  style={{
                    background: currentUser?.avatarUrl
                      ? `url(${currentUser.avatarUrl}) center/cover no-repeat`
                      : 'linear-gradient(135deg, #d35400, #e67e22)',
                  }}
                >
                  {!currentUser?.avatarUrl && <span>{userInitial}</span>}
                </div>

                {/* Botón flotante de cámara */}
                <button
                  type="button"
                  onClick={() => setPhotoModalOpen(true)}
                  title="Cambiar foto de perfil"
                  className="absolute -bottom-0.5 -right-0.5 w-[34px] h-[34px] rounded-full bg-[#232338] border-[3px] border-[#1a1a2e] text-[#a0a0a0] hover:text-[#e94560] hover:scale-110 flex items-center justify-center transition-all cursor-pointer shadow-lg"
                >
                  <Camera className="h-4 w-4" />
                </button>
                <input
                  type="file"
                  ref={fileInputRef}
                  onChange={handleFileChange}
                  accept="image/*"
                  className="hidden"
                />
              </div>

              {/* Nombre con icono de edición */}
              <div className="profile-name-wrapper flex items-center justify-center gap-3 mb-1">
                <h1 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                  {currentUser?.name}
                </h1>
                <button
                  type="button"
                  onClick={() => setEditModalOpen(true)}
                  title="Editar nombre y usuario"
                  className="w-[34px] h-[34px] rounded-full bg-white/[0.06] hover:bg-[#e94560]/15 text-[#a0a0a0] hover:text-[#e94560] hover:scale-105 flex items-center justify-center cursor-pointer transition-all"
                >
                  <Pencil className="h-4 w-4" />
                </button>
              </div>
              <p className="text-[0.95rem] font-bold text-[#e94560] mb-1">@{currentUser?.username}</p>
              <p className="text-[0.925rem] text-[#a0a0a0] mb-8">{currentUser?.email}</p>

              {/* Estadísticas: Comentarios y Reputación (Card exacta de la foto) */}
              <div className="w-full max-w-[460px] bg-[#16213e] border border-white/[0.08] rounded-[20px] p-[10px_15px] flex items-center justify-around shadow-2xl mb-10">
                {/* Comentarios */}
                <div className="flex items-center gap-3 flex-1 justify-center">
                  <div className="w-10 h-10 rounded-[14px] bg-[rgba(66,133,244,0.15)] text-[#4285f4] flex items-center justify-center shrink-0">
                    <MessageSquare className="h-5 w-5 fill-current" />
                  </div>
                  <div className="flex flex-col items-start gap-0.5">
                    <span className="text-xl font-bold text-white leading-none block">
                      {dashboard?.commentsCount ?? 0}
                    </span>
                    <span className="text-[10px] font-bold tracking-wider text-[#a0a0a0] uppercase">
                      COMENTARIOS
                    </span>
                  </div>
                </div>

                {/* Separador */}
                <div className="w-[1px] h-12 bg-white/[0.08] mx-2 shrink-0" />

                {/* Reputación */}
                <div className="flex items-center gap-3 flex-1 justify-center">
                  <div className="w-10 h-10 rounded-[14px] bg-[rgba(251,188,5,0.15)] text-[#fbbc05] flex items-center justify-center shrink-0">
                    <Star className="h-5 w-5 fill-[#fbbc05]" />
                  </div>
                  <div className="flex flex-col items-start gap-0.5">
                    <span className="text-xl font-bold text-white leading-none block">
                      {dashboard?.reputation ?? currentUser?.reputation ?? 0}
                    </span>
                    <span className="text-[10px] font-bold tracking-wider text-[#a0a0a0] uppercase">
                      REPUTACIÓN
                    </span>
                  </div>
                </div>
              </div>

              {/* Campo / Cápsula de Descripción (Matching foto de referencia) */}
              <div className="w-full max-w-[580px] mb-8">
                <div className="relative flex items-center bg-[#16213e] border border-white/[0.08] rounded-full px-5 h-[50px] transition-all hover:bg-[#16213e]/80 focus-within:border-[#e94560] focus-within:ring-4 focus-within:ring-[#e94560]/15">
                  <span className="text-[#a0a0a0] mr-3.5 flex items-center justify-center shrink-0">
                    <Pencil className="h-4 w-4" />
                  </span>
                  <input
                    type="text"
                    value={bioInput}
                    onChange={(e) => setBioInput(e.target.value)}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter') {
                        e.currentTarget.blur();
                        updateProfileMutation.mutate({ bio: bioInput });
                      }
                    }}
                    onBlur={() => {
                      if (bioInput !== (currentUser?.bio || '')) {
                        updateProfileMutation.mutate({ bio: bioInput });
                      }
                    }}
                    placeholder="Agregar descripción"
                    maxLength={100}
                    className="w-full bg-transparent border-none text-white text-[0.95rem] outline-none placeholder-[#a0a0a0]/80"
                  />
                  {updateProfileMutation.isPending && (
                    <span className="text-xs text-[#e94560] font-semibold animate-pulse ml-2">
                      Guardando...
                    </span>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* ========================================================= */}
          {/* TAB 2: MIS RESEÑAS                                        */}
          {/* ========================================================= */}
          {activeSection === 'resenas' && (
            <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in-50 duration-200">
              <div className="border-b border-[#0f3460]/60 pb-4">
                <h2 className="text-2xl font-bold text-white tracking-tight">Mis reseñas</h2>
                <p className="text-xs text-[#a0a0a0] mt-1">
                  Aquí verás las películas que has calificado y comentado en NexoHub.
                </p>
              </div>

              {isLoading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                  {Array.from({ length: 3 }).map((_, i) => (
                    <Skeleton key={i} className="h-64 w-full rounded-2xl" />
                  ))}
                </div>
              ) : dashboard?.reviews && dashboard.reviews.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                  {dashboard.reviews.map((rev) => (
                    <Link
                      key={rev.id}
                      to={`/peliculas/${rev.movieId}`}
                      className="group bg-[#16213e] border border-[#0f3460] rounded-2xl overflow-hidden hover:border-[#e94560] transition-all duration-200 shadow-lg flex flex-col"
                    >
                      <div className="aspect-[16/10] w-full bg-[#1a1a2e] relative overflow-hidden">
                        <img
                          src={rev.posterUrl || fallbackPoster}
                          alt={rev.title}
                          className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                          onError={(e) => {
                            (e.currentTarget as HTMLImageElement).src = fallbackPoster;
                          }}
                        />
                        <div className="absolute top-2 right-2">
                          <StarRating value={rev.rating} readOnly size="sm" />
                        </div>
                      </div>

                      <div className="p-4 flex-1 flex flex-col justify-between space-y-2">
                        <div>
                          <h3 className="font-bold text-sm text-white group-hover:text-[#e94560] transition-colors truncate">
                            {rev.title}
                          </h3>
                          <p className="text-xs text-[#cbd5e1] line-clamp-3 mt-1 leading-relaxed">
                            {rev.content || 'Sin comentario escrito'}
                          </p>
                        </div>
                        <p className="text-[10px] text-[#a0a0a0] pt-2 border-t border-[#0f3460]/40">
                          {new Date(rev.createdAt).toLocaleDateString('es-AR')}
                        </p>
                      </div>
                    </Link>
                  ))}
                </div>
              ) : (
                <div className="py-20 text-center bg-[#16213e] border border-[#0f3460] rounded-2xl space-y-3">
                  <MessageSquare className="h-10 w-10 text-[#a0a0a0] mx-auto opacity-40" />
                  <h3 className="text-base font-semibold text-white">Todavía no has hecho reseñas</h3>
                  <p className="text-xs text-[#a0a0a0]">
                    Explora el catálogo, busca una película y sé el primero en compartir tu crítica.
                  </p>
                  <Link to="/explorar">
                    <Button variant="primary" size="sm">
                      Explorar Películas
                    </Button>
                  </Link>
                </div>
              )}
            </div>
          )}

          {/* ========================================================= */}
          {/* TAB 3: PELÍCULAS FAVORITAS                                */}
          {/* ========================================================= */}
          {activeSection === 'favoritas' && (
            <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in-50 duration-200">
              <div className="border-b border-[#0f3460]/60 pb-4">
                <h2 className="text-2xl font-bold text-white tracking-tight">Películas favoritas</h2>
                <p className="text-xs text-[#a0a0a0] mt-1">
                  Tus películas guardadas en tu colección personal con estrella.
                </p>
              </div>

              {isLoading ? (
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                  {Array.from({ length: 4 }).map((_, i) => (
                    <Skeleton key={i} className="aspect-[2/3] w-full rounded-2xl" />
                  ))}
                </div>
              ) : dashboard?.favorites && dashboard.favorites.length > 0 ? (
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
                  {dashboard.favorites.map((fav) => (
                    <Link
                      key={fav.id}
                      to={`/peliculas/${fav.id}`}
                      className="group bg-[#16213e] border border-[#0f3460] rounded-2xl overflow-hidden hover:border-[#e94560] transition-all duration-200 shadow-lg flex flex-col"
                    >
                      <div className="aspect-[2/3] w-full bg-[#1a1a2e] overflow-hidden">
                        <img
                          src={fav.posterUrl || fallbackPoster}
                          alt={fav.title}
                          className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                          onError={(e) => {
                            (e.currentTarget as HTMLImageElement).src = fallbackPoster;
                          }}
                        />
                      </div>
                      <div className="p-3">
                        <h3 className="font-bold text-xs text-white group-hover:text-[#e94560] transition-colors truncate">
                          {fav.title}
                        </h3>
                      </div>
                    </Link>
                  ))}
                </div>
              ) : (
                <div className="py-20 text-center bg-[#16213e] border border-[#0f3460] rounded-2xl space-y-3">
                  <Star className="h-10 w-10 text-[#a0a0a0] mx-auto opacity-40" />
                  <h3 className="text-base font-semibold text-white">No tienes películas favoritas</h3>
                  <p className="text-xs text-[#a0a0a0]">
                    Entra a cualquier película del catálogo y pulsa la estrella para guardarla aquí.
                  </p>
                  <Link to="/explorar">
                    <Button variant="primary" size="sm">
                      Ir al Catálogo
                    </Button>
                  </Link>
                </div>
              )}
            </div>
          )}

          {/* ========================================================= */}
          {/* TAB 4: MI ACTIVIDAD                                       */}
          {/* ========================================================= */}
          {activeSection === 'actividad' && (
            <div className="max-w-3xl mx-auto space-y-6 animate-in fade-in-50 duration-200">
              <div className="border-b border-[#0f3460]/60 pb-4">
                <h2 className="text-2xl font-bold text-white tracking-tight">Mi actividad</h2>
                <p className="text-xs text-[#a0a0a0] mt-1">
                  Registro cronológico de tus reseñas y comentarios en NexoHub.
                </p>
              </div>

              {dashboard?.activity && dashboard.activity.length > 0 ? (
                <div className="space-y-3">
                  {dashboard.activity.map((act) => (
                    <Link
                      key={act.id}
                      to={`/peliculas/${act.movieId}`}
                      className="block p-4 rounded-2xl bg-[#16213e] border border-[#0f3460] hover:border-[#e94560] transition-all shadow-md group"
                    >
                      <div className="flex items-center justify-between gap-2 mb-1.5">
                        <div className="flex items-center gap-2">
                          <span
                            className={cn(
                              'text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full',
                              act.type === 'review'
                                ? 'bg-[#e94560]/20 text-[#e94560]'
                                : 'bg-sky-500/20 text-sky-400',
                            )}
                          >
                            {act.type === 'review' ? 'Reseña' : 'Comentario'}
                          </span>
                          <strong className="text-sm font-bold text-white group-hover:text-[#e94560] transition-colors">
                            {act.movieTitle}
                          </strong>
                        </div>
                        <span className="text-[11px] text-[#a0a0a0]">
                          {new Date(act.createdAt).toLocaleDateString('es-AR')}
                        </span>
                      </div>
                      <p className="text-xs text-[#cbd5e1] leading-relaxed line-clamp-2">
                        {act.comment}
                      </p>
                    </Link>
                  ))}
                </div>
              ) : (
                <div className="py-20 text-center bg-[#16213e] border border-[#0f3460] rounded-2xl space-y-2">
                  <Clock className="h-10 w-10 text-[#a0a0a0] mx-auto opacity-40" />
                  <p className="text-sm text-[#cbd5e1]">Todavía no hay actividad para mostrar.</p>
                </div>
              )}
            </div>
          )}

          {/* ========================================================= */}
          {/* TAB 5: CONFIGURACIÓN                                      */}
          {/* ========================================================= */}
          {activeSection === 'config' && (
            <div className="max-w-2xl mx-auto space-y-6 animate-in fade-in-50 duration-200">
              <div className="border-b border-[#0f3460]/60 pb-4">
                <h2 className="text-2xl font-bold text-white tracking-tight">Configuración</h2>
                <p className="text-xs text-[#a0a0a0] mt-1">
                  Ajusta la privacidad de tu cuenta y gestiona tus preferencias.
                </p>
              </div>

              <div className="bg-[#16213e] border border-[#0f3460] rounded-2xl overflow-hidden divide-y divide-[#0f3460]/60 shadow-xl">
                {/* Visibilidad del perfil */}
                <div className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div className="space-y-0.5">
                    <h4 className="text-sm font-bold text-white">Visibilidad del perfil</h4>
                    <p className="text-xs text-[#a0a0a0]">
                      Elige si otras personas pueden ver tu lista de películas favoritas.
                    </p>
                  </div>

                  <div className="inline-flex rounded-xl p-1 bg-[#0f172a] border border-[#0f3460]">
                    <button
                      type="button"
                      onClick={() => updateProfileMutation.mutate({ isPublic: true })}
                      className={cn(
                        'px-4 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                        currentUser?.isPublic
                          ? 'bg-[#e94560] text-white shadow-md'
                          : 'text-[#a0a0a0] hover:text-white',
                      )}
                    >
                      Público
                    </button>
                    <button
                      type="button"
                      onClick={() => updateProfileMutation.mutate({ isPublic: false })}
                      className={cn(
                        'px-4 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                        !currentUser?.isPublic
                          ? 'bg-[#e94560] text-white shadow-md'
                          : 'text-[#a0a0a0] hover:text-white',
                      )}
                    >
                      Privado
                    </button>
                  </div>
                </div>

                {/* Rol / Cuenta */}
                <div className="p-5 flex items-center justify-between gap-4">
                  <div className="space-y-0.5">
                    <h4 className="text-sm font-bold text-white">Rol en la plataforma</h4>
                    <p className="text-xs text-[#a0a0a0]">Nivel de privilegios y moderación.</p>
                  </div>
                  <Badge variant={currentUser?.role === 'SUPERADMIN' ? 'purple' : currentUser?.role === 'ADMIN' ? 'amber' : 'zinc'}>
                    {currentUser?.role}
                  </Badge>
                </div>

                {/* Eliminar cuenta */}
                <div className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-red-500/5">
                  <div className="space-y-0.5">
                    <h4 className="text-sm font-bold text-red-300">Eliminar cuenta</h4>
                    <p className="text-xs text-[#a0a0a0]">
                      Borra de forma permanente tu perfil, reseñas, comentarios y reputación.
                    </p>
                  </div>
                  <Button
                    variant="danger"
                    size="sm"
                    onClick={() => setDeleteAccountModalOpen(true)}
                  >
                    Eliminar cuenta
                  </Button>
                </div>
              </div>
            </div>
          )}
        </div>
      </main>

      {/* ========================================================= */}
      {/* MODAL: EDITAR NOMBRE Y USERNAME */}
      {/* ========================================================= */}
      <Dialog
        open={editModalOpen}
        onClose={() => setEditModalOpen(false)}
        title="Editar perfil"
        description="Actualiza tu nombre visible y tu identificador de usuario."
      >
        <form
          onSubmit={(e) => {
            e.preventDefault();
            if (!editName.trim() || !editUsername.trim()) {
              toast.error('Por favor completa todos los campos');
              return;
            }
            updateProfileMutation.mutate({
              name: editName.trim(),
              username: editUsername.trim(),
            });
          }}
          className="space-y-4"
        >
          <Input
            label="Nombre"
            value={editName}
            onChange={(e) => setEditName(e.target.value)}
            maxLength={40}
            required
          />

          <Input
            label="Nombre de Usuario"
            value={editUsername}
            onChange={(e) => setEditUsername(e.target.value)}
            maxLength={20}
            required
          />

          <div className="flex justify-end gap-2 pt-2">
            <Button variant="ghost" type="button" onClick={() => setEditModalOpen(false)}>
              Cancelar
            </Button>
            <Button variant="primary" type="submit" isLoading={updateProfileMutation.isPending}>
              Guardar cambios
            </Button>
          </div>
        </form>
      </Dialog>

      {/* ========================================================= */}
      {/* MODAL: CAMBIAR FOTO DE PERFIL                             */}
      {/* ========================================================= */}
      <Dialog
        open={photoModalOpen}
        onClose={() => setPhotoModalOpen(false)}
        title="Cambiar foto de perfil"
        description="Sube una imagen desde tu dispositivo o ingresa una URL directa."
      >
        <div className="space-y-5">
          {/* Opción 1: Subir archivo */}
          <div className="p-4 rounded-xl bg-[#0f172a] border border-[#0f3460] text-center space-y-2">
            <p className="text-xs text-[#cbd5e1] font-medium">Subir desde tu computadora</p>
            <Button
              variant="secondary"
              size="sm"
              onClick={() => fileInputRef.current?.click()}
              leftIcon={<Upload className="h-4 w-4" />}
            >
              Seleccionar archivo
            </Button>
          </div>

          <div className="flex items-center gap-3">
            <div className="h-px flex-1 bg-[#0f3460]" />
            <span className="text-[11px] uppercase tracking-wider text-[#a0a0a0]">O mediante URL</span>
            <div className="h-px flex-1 bg-[#0f3460]" />
          </div>

          {/* Opción 2: URL de imagen */}
          <div className="space-y-2">
            <Input
              label="URL directa de la imagen"
              value={photoUrlInput}
              onChange={(e) => setPhotoUrlInput(e.target.value)}
              placeholder="https://ejemplo.com/mi-avatar.jpg"
            />
            <Button
              variant="primary"
              size="sm"
              className="w-full"
              disabled={!photoUrlInput.trim()}
              onClick={() => updateProfileMutation.mutate({ avatarUrl: photoUrlInput.trim() })}
              isLoading={updateProfileMutation.isPending}
            >
              Actualizar mediante URL
            </Button>
          </div>

          {currentUser?.avatarUrl && (
            <div className="pt-2 border-t border-[#0f3460]/40">
              <Button
                variant="danger"
                size="sm"
                className="w-full"
                onClick={() => updateProfileMutation.mutate({ avatarUrl: null })}
                isLoading={updateProfileMutation.isPending}
                leftIcon={<Trash2 className="h-4 w-4" />}
              >
                Eliminar foto actual
              </Button>
            </div>
          )}
        </div>
      </Dialog>

      {/* ========================================================= */}
      {/* MODAL: CONFIRMACIÓN PARA ELIMINAR CUENTA                 */}
      {/* ========================================================= */}
      <ConfirmDialog
        open={deleteAccountModalOpen}
        onClose={() => setDeleteAccountModalOpen(false)}
        onConfirm={() => {
          deleteAccountMutation.mutate(undefined, {
            onSettled: () => setDeleteAccountModalOpen(false),
          });
        }}
        title="¿Eliminar cuenta permanentemente?"
        description="Esta acción es irreversible y borrará tu perfil, reseñas, comentarios, votos y películas favoritas de NexoHub."
        confirmText="Eliminar cuenta"
        cancelText="Cancelar"
        variant="danger"
        isLoading={deleteAccountMutation.isPending}
      />
    </div>
  );
}
