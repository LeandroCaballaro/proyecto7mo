import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Clapperboard, Lock, Mail, User } from 'lucide-react';
import { toast } from 'sonner';
import { Button, Input } from '../components/ui';
import { useAuthStore } from '../stores/auth.store';

export function LoginPage() {
  const navigate = useNavigate();
  const { login } = useAuthStore();

  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');

    if (!identifier.trim() || !password) {
      setError('Por favor completa todos los campos');
      return;
    }

    setIsLoading(true);
    try {
      await login({ identifier: identifier.trim(), password });
      toast.success('Sesión iniciada correctamente');
      navigate('/');
    } catch (err: any) {
      setError(err.message || 'Credenciales incorrectas');
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <div className="min-h-[80vh] flex items-center justify-center py-12 px-4">
      <div className="w-full max-w-md space-y-6 bg-[#16213e] border border-[#0f3460] p-8 rounded-3xl shadow-2xl">
        <div className="text-center space-y-2">
          <div className="h-12 w-12 rounded-2xl bg-[#e94560] flex items-center justify-center text-white mx-auto shadow-lg shadow-[#e94560]/30">
            <Clapperboard className="h-6 w-6" />
          </div>
          <h2 className="text-2xl font-black text-[#f5f5f5]">Iniciar Sesión</h2>
          <p className="text-xs text-[#a0a0a0]">Ingresa tu correo electrónico o nombre de usuario</p>
        </div>

        {error && (
          <div className="p-3 rounded-xl bg-red-950/60 border border-red-800/60 text-xs text-red-300 font-medium">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="Email o Usuario"
            placeholder="admin o usuario@correo.com"
            value={identifier}
            onChange={(e) => setIdentifier(e.target.value)}
            leftIcon={<User className="h-4 w-4" />}
          />

          <Input
            type="password"
            label="Contraseña"
            placeholder="Ingresa tu contraseña"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            leftIcon={<Lock className="h-4 w-4" />}
          />

          <Button type="submit" className="w-full" size="lg" isLoading={isLoading}>
            Ingresar a NexoHub
          </Button>
        </form>

        <div className="text-center pt-2 border-t border-zinc-800">
          <p className="text-xs text-zinc-400">
            ¿No tienes cuenta?{' '}
            <Link to="/registro" className="text-rose-400 hover:text-rose-300 font-semibold">
              Crear una cuenta gratis
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}

