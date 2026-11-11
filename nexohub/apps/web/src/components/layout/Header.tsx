import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import {
  Heart,
  LogOut,
  Menu,
  PlusCircle,
  Search,
  ShieldCheck,
  Star,
  User,
  X,
} from 'lucide-react';
import { isStaff } from '@nexohub/shared';
import { useAuthStore } from '../../stores/auth.store';
import { Badge, Button } from '../ui';

export function Header({ onOpenPropose }: { onOpenPropose: () => void }) {
  const { user, logout } = useAuthStore();
  const location = useLocation();
  const navigate = useNavigate();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [userDropdownOpen, setUserDropdownOpen] = useState(false);

  const staff = isStaff(user?.role);

  async function handleLogout() {
    await logout();
    setUserDropdownOpen(false);
    navigate('/');
  }

  const isHome = location.pathname === '/';
  const isExplorar = location.pathname.startsWith('/explorar');
  const isGeneros = location.pathname.startsWith('/generos');
  const isRanking = location.pathname.startsWith('/ranking');
  const isAdmin = location.pathname.startsWith('/admin');

  return (
    <header className="sticky top-0 z-50 w-full border-b border-[#0f3460] header-glass transition-all duration-300">
      <div className="max-w-7xl mx-auto flex h-16 items-center justify-between px-4 sm:px-6">
        {/* Logo Original */}
        <Link to="/" className="flex items-center gap-3 group">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e94560] transition-transform duration-300 group-hover:scale-105 shadow-lg shadow-[#e94560]/25">
            <svg
              className="h-6 w-6 text-white ml-0.5"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path d="M7 4v16l13-8L7 4z" />
            </svg>
          </div>
          <span className="text-2xl font-black text-[#f5f5f5] tracking-tight group-hover:opacity-90 transition-opacity">
            Nexo<span className="text-[#e94560]">Hub</span>
          </span>
        </Link>

        {/* Desktop Navigation */}
        <nav className="hidden items-center gap-8 md:flex">
          <Link
            to="/"
            className={`text-sm font-semibold nav-link ${
              isHome ? 'text-[#e94560] active' : 'text-[#a0a0a0] hover:text-[#e94560]'
            }`}
          >
            Inicio
          </Link>
          <Link
            to="/explorar"
            className={`text-sm font-semibold nav-link ${
              isExplorar ? 'text-[#e94560] active' : 'text-[#a0a0a0] hover:text-[#e94560]'
            }`}
          >
            Explorar
          </Link>
          <Link
            to="/generos"
            className={`text-sm font-semibold nav-link ${
              isGeneros ? 'text-[#e94560] active' : 'text-[#a0a0a0] hover:text-[#e94560]'
            }`}
          >
            Géneros
          </Link>
          <Link
            to="/ranking"
            className={`text-sm font-semibold nav-link ${
              isRanking ? 'text-[#e94560] active' : 'text-[#a0a0a0] hover:text-[#e94560]'
            }`}
          >
            Ranking
          </Link>
          {staff && (
            <Link
              to="/admin"
              className={`text-sm font-semibold nav-link ${
                isAdmin ? 'text-[#e94560] active' : 'text-[#a0a0a0] hover:text-[#e94560]'
              }`}
            >
              Administración
            </Link>
          )}
        </nav>

        {/* Desktop Actions */}
        <div className="hidden items-center gap-3 md:flex">
          <Link
            to="/explorar"
            className="text-[#a0a0a0] hover:text-[#e94560] transition-colors p-2"
            title="Buscar"
          >
            <Search className="h-5 w-5" />
          </Link>

          <Link
            to={user ? '/favoritos' : '/login'}
            className="text-[#a0a0a0] hover:text-[#e94560] transition-colors p-2"
            title="Favoritos"
          >
            <Star className="h-5 w-5" />
          </Link>

          {user ? (
            <div className="flex items-center gap-3">
              <Link to="/proponer">
                <Button
                  variant="outline"
                  size="sm"
                  leftIcon={<PlusCircle className="h-4 w-4 text-[#e94560]" />}
                >
                  Proponer
                </Button>
              </Link>

              <div className="relative">
                <button
                  type="button"
                  onClick={() => setUserDropdownOpen((v) => !v)}
                  className="flex items-center gap-2.5 p-1 rounded-xl hover:bg-[#16213e] border border-transparent hover:border-[#0f3460] transition-all cursor-pointer"
                >
                  <div className="h-8 w-8 rounded-lg bg-[#0f3460] border border-[#16213e] flex items-center justify-center font-bold text-xs text-[#e94560] overflow-hidden">
                    {user.avatarUrl ? (
                      <img src={user.avatarUrl} alt={user.name} className="h-full w-full object-cover" />
                    ) : (
                      user.name.charAt(0).toUpperCase()
                    )}
                  </div>
                  <div className="text-left hidden lg:block">
                    <p className="text-xs font-semibold text-[#f5f5f5] leading-none">{user.name}</p>
                    <p className="text-[10px] text-[#a0a0a0] mt-0.5">@{user.username}</p>
                  </div>
                </button>

                {userDropdownOpen && (
                  <div
                    className="absolute right-0 mt-2 w-56 rounded-2xl bg-[#16213e] border border-[#0f3460] shadow-2xl p-1.5 z-50 backdrop-blur-xl animate-in fade-in-0 zoom-in-95"
                    onClick={() => setUserDropdownOpen(false)}
                  >
                    <div className="px-3 py-2 border-b border-[#0f3460] mb-1">
                      <p className="text-xs font-semibold text-[#f5f5f5]">{user.name}</p>
                      <p className="text-[11px] text-[#a0a0a0] truncate">{user.email}</p>
                      <div className="mt-1.5">
                        <Badge variant={user.role === 'SUPERADMIN' ? 'purple' : user.role === 'ADMIN' ? 'amber' : 'zinc'} size="sm">
                          {user.role}
                        </Badge>
                      </div>
                    </div>

                    <Link
                      to="/perfil"
                      className="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-[#f5f5f5] hover:bg-[#0f3460] transition-colors"
                    >
                      <User className="h-4 w-4 text-[#a0a0a0]" />
                      Mi Perfil
                    </Link>

                    <Link
                      to="/favoritos"
                      className="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-[#f5f5f5] hover:bg-[#0f3460] transition-colors"
                    >
                      <Heart className="h-4 w-4 text-[#e94560]" />
                      Mis Favoritas
                    </Link>

                    {staff && (
                      <Link
                        to="/admin"
                        className="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-amber-300 hover:bg-[#0f3460] transition-colors"
                      >
                        <ShieldCheck className="h-4 w-4 text-amber-400" />
                        Administración
                      </Link>
                    )}

                    <div className="my-1 border-t border-[#0f3460]" />

                    <button
                      type="button"
                      onClick={handleLogout}
                      className="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-red-400 hover:bg-red-950/30 transition-colors cursor-pointer"
                    >
                      <LogOut className="h-4 w-4" />
                      Cerrar Sesión
                    </button>
                  </div>
                )}
              </div>
            </div>
          ) : (
            <div className="flex items-center gap-2.5">
              <Link to="/login">
                <button
                  type="button"
                  className="px-4 py-2 rounded-lg text-sm font-medium border border-[#0f3460] text-[#f5f5f5] hover:bg-[#16213e] hover:border-[#e94560]/40 transition-colors cursor-pointer"
                >
                  Log In
                </button>
              </Link>
              <Link to="/registro">
                <button
                  type="button"
                  className="px-4 py-2 rounded-lg text-sm font-semibold bg-[#e94560] text-white hover:bg-[#e94560]/90 shadow-md shadow-[#e94560]/20 transition-all cursor-pointer"
                >
                  Sign Up
                </button>
              </Link>
            </div>
          )}
        </div>

        {/* Mobile menu trigger */}
        <button
          type="button"
          onClick={() => setMobileMenuOpen((v) => !v)}
          className="md:hidden p-2 rounded-xl text-[#a0a0a0] hover:text-white"
        >
          {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </div>

      {/* Mobile Drawer */}
      {mobileMenuOpen && (
        <div className="md:hidden border-t border-[#0f3460] bg-[#1a1a2e] px-4 pt-3 pb-6 space-y-3">
          <nav className="space-y-1">
            <Link
              to="/"
              onClick={() => setMobileMenuOpen(false)}
              className="block px-3 py-2 rounded-lg text-sm font-medium text-[#f5f5f5] hover:bg-[#16213e]"
            >
              Inicio
            </Link>
            <Link
              to="/explorar"
              onClick={() => setMobileMenuOpen(false)}
              className="block px-3 py-2 rounded-lg text-sm font-medium text-[#a0a0a0] hover:bg-[#16213e] hover:text-white"
            >
              Explorar
            </Link>
            <Link
              to="/generos"
              onClick={() => setMobileMenuOpen(false)}
              className="block px-3 py-2 rounded-lg text-sm font-medium text-[#a0a0a0] hover:bg-[#16213e] hover:text-white"
            >
              Géneros
            </Link>
            <Link
              to="/ranking"
              onClick={() => setMobileMenuOpen(false)}
              className="block px-3 py-2 rounded-lg text-sm font-medium text-[#a0a0a0] hover:bg-[#16213e] hover:text-white"
            >
              Ranking
            </Link>
            {staff && (
              <Link
                to="/admin"
                onClick={() => setMobileMenuOpen(false)}
                className="block px-3 py-2 rounded-lg text-sm font-medium text-amber-400 hover:bg-[#16213e]"
              >
                Administración
              </Link>
            )}
          </nav>

          <div className="pt-3 border-t border-[#0f3460] space-y-2">
            {user ? (
              <>
                <Link to="/proponer" onClick={() => setMobileMenuOpen(false)}>
                  <Button
                    variant="outline"
                    className="w-full justify-start"
                    leftIcon={<PlusCircle className="h-4 w-4 text-[#e94560]" />}
                  >
                    Proponer Película
                  </Button>
                </Link>
                <Link
                  to="/perfil"
                  onClick={() => setMobileMenuOpen(false)}
                  className="block px-3 py-2 text-sm text-[#f5f5f5]"
                >
                  Mi Perfil (@{user.username})
                </Link>
                <Button variant="danger" className="w-full justify-start" onClick={handleLogout}>
                  Cerrar Sesión
                </Button>
              </>
            ) : (
              <div className="grid grid-cols-2 gap-2">
                <Link to="/login" onClick={() => setMobileMenuOpen(false)}>
                  <Button variant="outline" className="w-full">
                    Log In
                  </Button>
                </Link>
                <Link to="/registro" onClick={() => setMobileMenuOpen(false)}>
                  <Button variant="primary" className="w-full">
                    Sign Up
                  </Button>
                </Link>
              </div>
            )}
          </div>
        </div>
      )}
    </header>
  );
}
