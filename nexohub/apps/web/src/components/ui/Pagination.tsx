import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '../../lib/cn';

export interface PaginationProps {
  page: number;
  totalPages: number;
  onPageChange: (newPage: number) => void;
  className?: string;
}

export function Pagination({ page, totalPages, onPageChange, className }: PaginationProps) {
  if (totalPages <= 1) return null;

  const pages = Array.from({ length: totalPages }, (_, i) => i + 1);

  return (
    <nav className={cn('flex items-center justify-center gap-1.5 select-none', className)}>
      <button
        type="button"
        disabled={page <= 1}
        onClick={() => onPageChange(page - 1)}
        className="h-9 w-9 flex items-center justify-center rounded-xl bg-zinc-900 border border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800 disabled:opacity-30 disabled:pointer-events-none transition-colors cursor-pointer"
      >
        <ChevronLeft className="h-4 w-4" />
      </button>

      {pages.map((p) => {
        const isCurrent = p === page;
        return (
          <button
            key={p}
            type="button"
            onClick={() => onPageChange(p)}
            className={cn(
              'h-9 min-w-9 px-2 text-xs font-semibold rounded-xl transition-all cursor-pointer',
              isCurrent
                ? 'bg-rose-600 text-white shadow-md shadow-rose-900/30'
                : 'bg-zinc-900 border border-zinc-800 text-zinc-300 hover:bg-zinc-800 hover:text-white',
            )}
          >
            {p}
          </button>
        );
      })}

      <button
        type="button"
        disabled={page >= totalPages}
        onClick={() => onPageChange(page + 1)}
        className="h-9 w-9 flex items-center justify-center rounded-xl bg-zinc-900 border border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800 disabled:opacity-30 disabled:pointer-events-none transition-colors cursor-pointer"
      >
        <ChevronRight className="h-4 w-4" />
      </button>
    </nav>
  );
}

