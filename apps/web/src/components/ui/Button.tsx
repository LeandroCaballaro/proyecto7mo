import React, { forwardRef } from 'react';
import { Loader2 } from 'lucide-react';
import { cn } from '../../lib/cn';

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
  size?: 'sm' | 'md' | 'lg' | 'icon';
  isLoading?: boolean;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  (
    {
      className,
      variant = 'primary',
      size = 'md',
      isLoading = false,
      leftIcon,
      rightIcon,
      disabled,
      children,
      ...props
    },
    ref,
  ) => {
    const base =
      'inline-flex items-center justify-center font-semibold rounded-lg transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#e94560]/50 disabled:pointer-events-none disabled:opacity-50 select-none cursor-pointer';

    const variants = {
      primary:
        'bg-[#e94560] hover:bg-[#e94560]/90 text-white shadow-md shadow-[#e94560]/20 active:scale-[0.98]',
      secondary:
        'bg-[#16213e] hover:bg-[#0f3460] text-[#f5f5f5] border border-[#0f3460] active:scale-[0.98]',
      outline:
        'border border-[#0f3460] bg-transparent hover:bg-[#16213e] text-[#f5f5f5] active:scale-[0.98]',
      ghost: 'bg-transparent hover:bg-[#16213e] text-[#a0a0a0] hover:text-[#f5f5f5]',
      danger:
        'bg-red-950/70 text-red-300 border border-red-800/60 hover:bg-red-900/80 active:scale-[0.98]',
    };

    const sizes = {
      sm: 'text-xs h-9 px-3 gap-1.5',
      md: 'text-sm h-10 px-4 gap-2',
      lg: 'text-base h-12 px-6 gap-2.5',
      icon: 'h-10 w-10 p-0',
    };

    return (
      <button
        ref={ref}
        disabled={disabled || isLoading}
        className={cn(base, variants[variant], sizes[size], className)}
        {...props}
      >
        {isLoading ? (
          <Loader2 className="h-4 w-4 animate-spin text-current" />
        ) : (
          <>
            {leftIcon}
            {children}
            {rightIcon}
          </>
        )}
      </button>
    );
  },
);

Button.displayName = 'Button';
