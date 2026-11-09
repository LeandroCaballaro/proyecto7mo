import React, { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '../../lib/cn';

export interface DialogProps {
  open: boolean;
  onClose: () => void;
  title?: string;
  description?: string;
  children: React.ReactNode;
  className?: string;
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl';
}

export function Dialog({
  open,
  onClose,
  title,
  description,
  children,
  className,
  maxWidth = 'md',
}: DialogProps) {
  useEffect(() => {
    function handleKeyDown(e: KeyboardEvent) {
      if (e.key === 'Escape' && open) {
        onClose();
      }
    }
    if (open) {
      document.body.style.overflow = 'hidden';
      window.addEventListener('keydown', handleKeyDown);
    } else {
      document.body.style.overflow = 'unset';
    }
    return () => {
      document.body.style.overflow = 'unset';
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [open, onClose]);

  if (!open) return null;

  const widths = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-2xl',
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-[#0c002a]/80 backdrop-blur-md transition-opacity animate-in fade-in-0 duration-200"
        onClick={onClose}
      />

      {/* Modal Card */}
      <div
        className={cn(
          'relative w-full bg-[#16213e] border border-[#0f3460] rounded-2xl shadow-2xl overflow-hidden z-10',
          'animate-in fade-in-0 zoom-in-95 duration-200',
          widths[maxWidth],
          className,
        )}
      >
        <div className="flex items-start justify-between p-5 border-b border-[#0f3460]">
          <div>
            {title && <h3 className="text-base font-bold text-[#f5f5f5]">{title}</h3>}
            {description && <p className="text-xs text-[#a0a0a0] mt-1">{description}</p>}
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1.5 rounded-lg text-[#a0a0a0] hover:text-white hover:bg-[#0f3460] transition-colors cursor-pointer"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <div className="p-5">{children}</div>
      </div>
    </div>
  );
}
