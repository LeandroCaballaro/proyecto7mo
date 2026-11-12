import { useEffect } from 'react';
import { Navigate, Route, Routes, useLocation } from 'react-router-dom';
import { Toaster } from 'sonner';
import { Footer } from './components/layout/Footer';
import { Header } from './components/layout/Header';
import { AdminRoute, ProtectedRoute } from './features/auth/ProtectedRoute';
import { AdminPage } from './pages/AdminPage';
import { ExplorePage } from './pages/ExplorePage';
import { FavoritesPage } from './pages/FavoritesPage';
import { GenresPage } from './pages/GenresPage';
import { HomePage } from './pages/HomePage';
import { LoginPage } from './pages/LoginPage';
import { MovieDetailPage } from './pages/MovieDetailPage';
import { ProfilePage } from './pages/ProfilePage';
import { ProposeMoviePage } from './pages/ProposeMoviePage';
import { RankingPage } from './pages/RankingPage';
import { RegisterPage } from './pages/RegisterPage';
import { UserProfileDashboard } from './pages/UserProfileDashboard';
import { useAuthStore } from './stores/auth.store';

export function App() {
  const { fetchMe } = useAuthStore();
  const location = useLocation();

  useEffect(() => {
    fetchMe();
  }, [fetchMe]);

  const isProfileDashboard = location.pathname.startsWith('/perfil');

  if (isProfileDashboard) {
    return (
      <div className="w-full h-screen overflow-hidden bg-[#1a1a2e] text-[#f5f5f5]">
        <Toaster
          theme="dark"
          position="top-right"
          toastOptions={{
            className: '!bg-[#16213e] !border-[#0f3460] !text-[#f5f5f5] !rounded-xl',
          }}
        />
        <Routes>
          <Route element={<ProtectedRoute />}>
            <Route path="/perfil" element={<UserProfileDashboard />} />
          </Route>
        </Routes>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col bg-[#1a1a2e] text-[#f5f5f5]">
      <Toaster
        theme="dark"
        position="top-right"
        toastOptions={{
          className: '!bg-[#16213e] !border-[#0f3460] !text-[#f5f5f5] !rounded-xl',
        }}
      />

      <Header />

      <main
        className={`flex-1 w-full ${location.pathname === '/' || location.pathname === '/generos' || location.pathname === '/ranking' ? '' : 'max-w-7xl mx-auto px-4 sm:px-6'} pt-6`}
      >
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/explorar" element={<ExplorePage />} />
          <Route path="/peliculas/:id" element={<MovieDetailPage />} />
          <Route path="/pelicula/:id" element={<MovieDetailPage />} />
          <Route path="/movies/:id" element={<MovieDetailPage />} />
          <Route path="/generos" element={<GenresPage />} />
          <Route path="/ranking" element={<RankingPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/registro" element={<RegisterPage />} />
          <Route path="/u/:username" element={<ProfilePage />} />

          {/* Rutas protegidas */}
          <Route element={<ProtectedRoute />}>
            <Route path="/favoritos" element={<FavoritesPage />} />
            <Route path="/proponer" element={<ProposeMoviePage />} />
            <Route path="/proponer-pelicula" element={<ProposeMoviePage />} />
            <Route path="/nueva-resena" element={<ProposeMoviePage />} />
          </Route>

          {/* Rutas administrativas */}
          <Route element={<AdminRoute />}>
            <Route path="/admin" element={<AdminPage />} />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </main>

      <Footer />
    </div>
  );
}
