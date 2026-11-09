import React from 'react';
import { cn } from '../../lib/cn';

export function Skeleton({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('animate-pulse rounded-xl bg-zinc-800/60', className)} {...props} />;
}

