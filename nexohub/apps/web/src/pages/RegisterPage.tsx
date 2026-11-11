import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Clapperboard, Lock, Mail, User } from 'lucide-react';
import { toast } from 'sonner';
import { Button, Input } from '../components/ui';
import { useAuthStore } from '../stores/auth.store';

export function RegisterPage() {
  const navigate = useNavigate();
  const { register } = useAuthStore();

  const [name, setName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [generalError, setGeneralError] = useState('');

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});
    setGeneralError('');

    if (password !== confirmPassword) {
      setErrors({ confirmPassword: 'Las contraseñas no coinciden' });
      return;
    }

    setIsLoading(true);
    try {
      await register({
        name: name.trim(),
        username: username.trim(),
        email: email.trim(),
        password,
        confirmPassword,
      });
      toast.success('Cuenta creada exitosamente');
      navigate('/');
    } catch (err: any) {
      if (err.details && Array.isArray(err.details)) {
        const errMap: Record<string, string> = {};
        for (const item of err.details) {
          const field = item.path.replace('body.', '');
          errMap[field] = item.message;
        }
        setErrors(errMap);
      } else {
        setGeneralError(err.message || 'Error al crear la cuenta');
      }
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <div className="min-h-[85vh] flex items-center justify-center py-12 px-4">
      <div className="w-full max-w-md space-y-6 bg-[#16213e] border border-[#0f3460] p-8 rounded-3xl shadow-2xl">
        <div className="text-center space-y-2">
          <div className="h-12 w-12 rounded-2xl bg-[#e94560] flex items-center justify-center text-white mx-auto shadow-lg shadow-[#e94560]/30">
            <Clapperboard className="h-6 w-6" />
          </div>
          <h2 className="text-2xl font-black text-[#f5f5f5]">Crear Cuenta</h2>
          <p className="text-xs text-[#a0a0a0]">Únete a la comunidad de críticos de cine</p>
        </div>

        {generalError && (
          <div className="p-3 rounded-xl bg-red-950/60 border border-red-800/60 text-xs text-red-300 font-medium">
            {generalError}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="Nombre Completo"
            placeholder="Ej. Sofia Martínez"
            value={name}
            onChange={(e) => setName(e.target.value)}
            leftIcon={<User className="h-4 w-4" />}
            error={errors.name}
          />

          <Input
            label="Nombre de Usuario"
            placeholder="Ej. sofiam"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            leftIcon={<User className="h-4 w-4" />}
            error={errors.username}
          />

          <Input
            type="email"
            label="Correo Electrónico"
            placeholder="correo@gmail.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            leftIcon={<Mail className="h-4 w-4" />}
            error={errors.email}
          />

          <Input
            type="password"
            label="Contraseña"
            placeholder="Mínimo 8 caracteres, 1 mayúscula, 1 número"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            leftIcon={<Lock className="h-4 w-4" />}
            error={errors.password}
          />

          <Input
            type="password"
            label="Confirmar Contraseña"
            placeholder="Repite tu contraseña"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            leftIcon={<Lock className="h-4 w-4" />}
            error={errors.confirmPassword}
          />

          <Button type="submit" className="w-full" size="lg" isLoading={isLoading}>
            Registrarme en NexoHub
          </Button>
        </form>

        <div className="text-center pt-2 border-t border-zinc-800">
          <p className="text-xs text-zinc-400">
            ¿Ya tienes una cuenta?{' '}
            <Link to="/login" className="text-rose-400 hover:text-rose-300 font-semibold">
              Iniciar Sesión
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}

