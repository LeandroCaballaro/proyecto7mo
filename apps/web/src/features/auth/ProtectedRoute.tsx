import { Navigate, Outlet } from 'react-router-dom';
import { isStaff } from '@nexohub/shared';
import { Skeleton } from '../../components/ui';
import { useAuthStore } from '../../stores/auth.store';

export function ProtectedRoute() {
  const { user, isLoading } = useAuthStore();

  if (isLoading) {
    return (
      <div className="py-20 flex justify-center">
        <Skeleton className="h-40 w-full max-w-xl rounded-2xl" />
      </div>
    );
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return <Outlet />;
}

export function AdminRoute() {
  const { user, isLoading } = useAuthStore();

  if (isLoading) {
    return (
      <div className="py-20 flex justify-center">
        <Skeleton className="h-40 w-full max-w-xl rounded-2xl" />
      </div>
    );
  }

  if (!user || !isStaff(user.role)) {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
}

