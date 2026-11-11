import React from 'react';
import { cn } from '../../lib/cn';

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
  variant?: 'default' | 'rose' | 'amber' | 'emerald' | 'purple' | 'zinc';
  size?: 'sm' | 'md';
}

export function Badge({ className, variant = 'default', size = 'md', children, ...props }: BadgeProps) {
  const variants = {
    default: 'bg-[#0f3460]/40 text-[#f5f5f5] border-[#0f3460]',
    rose: 'bg-[#e94560]/20 text-[#e94560] border-[#e94560]/30 font-bold',
    amber: 'bg-amber-500/20 text-amber-400 border-amber-500/30 font-bold',
    emerald: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30 font-medium',
    purple: 'bg-purple-500/20 text-purple-300 border-purple-500/30 font-medium',
    zinc: 'bg-[#0f172a] text-[#a0a0a0] border-[#0f3460]',
  };

  const sizes = {
    sm: 'text-[10px] px-2 py-0.5 font-medium tracking-wide uppercase',
    md: 'text-xs px-3 py-1 font-semibold tracking-wider uppercase',
  };

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1 rounded-full border',
        variants[variant],
        sizes[size],
        className,
      )}
      {...props}
    >
      {children}
    </span>
  );
}
