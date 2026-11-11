import { AlertTriangle, HelpCircle, Trash2 } from 'lucide-react';
import { Button } from './Button';
import { Dialog } from './Dialog';

export interface ConfirmDialogProps {
  open: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  description: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'danger' | 'primary' | 'warning';
  isLoading?: boolean;
}

export function ConfirmDialog({
  open,
  onClose,
  onConfirm,
  title,
  description,
  confirmText = 'Confirmar',
  cancelText = 'Cancelar',
  variant = 'danger',
  isLoading = false,
}: ConfirmDialogProps) {
  const iconConfig = {
    danger: {
      bg: 'bg-red-500/10 border-red-500/30 text-red-400',
      icon: <Trash2 className="h-6 w-6" />,
      btnVariant: 'danger' as const,
    },
    warning: {
      bg: 'bg-amber-500/10 border-amber-500/30 text-amber-400',
      icon: <AlertTriangle className="h-6 w-6" />,
      btnVariant: 'primary' as const,
    },
    primary: {
      bg: 'bg-[#0f3460] border-[#0f3460] text-[#38bdf8]',
      icon: <HelpCircle className="h-6 w-6" />,
      btnVariant: 'primary' as const,
    },
  }[variant];

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm">
      <div className="flex flex-col items-center text-center space-y-4 pt-1">
        <div
          className={`h-12 w-12 rounded-2xl border flex items-center justify-center shadow-lg ${iconConfig.bg}`}
        >
          {iconConfig.icon}
        </div>

        <div className="space-y-1.5">
          <h3 className="text-lg font-bold text-[#f5f5f5] tracking-tight">{title}</h3>
          <p className="text-xs text-[#a0a0a0] leading-relaxed max-w-sm">{description}</p>
        </div>

        <div className="flex items-center gap-3 w-full pt-4 border-t border-[#0f3460]/60">
          <Button
            type="button"
            variant="ghost"
            className="flex-1"
            onClick={onClose}
            disabled={isLoading}
          >
            {cancelText}
          </Button>

          <Button
            type="button"
            variant={iconConfig.btnVariant}
            className="flex-1"
            isLoading={isLoading}
            onClick={onConfirm}
          >
            {confirmText}
          </Button>
        </div>
      </div>
    </Dialog>
  );
}

