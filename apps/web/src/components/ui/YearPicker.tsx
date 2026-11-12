import { useEffect, useRef, useState } from 'react';
import { CalendarDays, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { cn } from '../../lib/cn';

export interface YearPickerProps {
  value?: number | null;
  onChange: (year: number | null) => void;
  label?: string;
  error?: string;
  placeholder?: string;
  minYear?: number;
  maxYear?: number;
  disabled?: boolean;
  className?: string;
}

export function YearPicker({
  value,
  onChange,
  label,
  error,
  placeholder = 'Año de estreno...',
  minYear = 1920,
  maxYear = new Date().getFullYear() + 4,
  disabled = false,
  className,
}: YearPickerProps) {
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  // Cada página muestra una cuadrícula de 12 años (ej. 2020 a 2031)
  const [decadeStart, setDecadeStart] = useState(() => {
    const y = value || new Date().getFullYear();
    return Math.floor(y / 12) * 12;
  });

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const years = Array.from({ length: 12 }, (_, i) => decadeStart + i);

  function prevDecade() {
    setDecadeStart((prev) => prev - 12);
  }

  function nextDecade() {
    setDecadeStart((prev) => prev + 12);
  }

  return (
    <div ref={containerRef} className={cn('relative w-full space-y-1.5', className)}>
      {label && <label className="block text-xs font-medium text-zinc-300">{label}</label>}

      <button
        type="button"
        disabled={disabled}
        onClick={() => setIsOpen((prev) => !prev)}
        className={cn(
          'w-full flex items-center justify-between gap-2 bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2.5 text-sm text-left',
          'transition-all duration-150 cursor-pointer',
          'focus:outline-none focus:border-rose-500/80 focus:ring-2 focus:ring-rose-500/20',
          'disabled:opacity-50 disabled:cursor-not-allowed',
          isOpen && 'border-rose-500/80 ring-2 ring-rose-500/20',
          error && 'border-red-500/80',
        )}
      >
        <div className="flex items-center gap-2.5">
          <CalendarDays className="h-4 w-4 text-zinc-400 shrink-0" />
          <span className={value ? 'text-zinc-100 font-medium' : 'text-zinc-500'}>
            {value ? String(value) : placeholder}
          </span>
        </div>

        {value && !disabled && (
          <span
            role="button"
            tabIndex={0}
            onClick={(e) => {
              e.stopPropagation();
              onChange(null);
            }}
            className="p-0.5 text-zinc-500 hover:text-zinc-300"
          >
            <X className="h-3.5 w-3.5" />
          </span>
        )}
      </button>

      {isOpen && (
        <div className="absolute z-50 left-0 mt-1 p-3 bg-zinc-900 border border-zinc-800 rounded-2xl shadow-2xl backdrop-blur-xl animate-in fade-in-0 zoom-in-95 duration-100 w-64">
          <div className="flex items-center justify-between mb-2 px-1">
            <span className="text-xs font-semibold text-zinc-400">
              {decadeStart} – {decadeStart + 11}
            </span>
            <div className="flex items-center gap-1">
              <button
                type="button"
                onClick={prevDecade}
                className="p-1 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer"
              >
                <ChevronLeft className="h-4 w-4" />
              </button>
              <button
                type="button"
                onClick={nextDecade}
                className="p-1 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer"
              >
                <ChevronRight className="h-4 w-4" />
              </button>
            </div>
          </div>

          <div className="grid grid-cols-3 gap-1.5 text-center">
            {years.map((yr) => {
              const isSelected = yr === value;
              const isCurrent = yr === new Date().getFullYear();
              const isOutOfRange = yr < minYear || yr > maxYear;

              return (
                <button
                  key={yr}
                  type="button"
                  disabled={isOutOfRange}
                  onClick={() => {
                    onChange(yr);
                    setIsOpen(false);
                  }}
                  className={cn(
                    'py-2 text-xs font-medium rounded-lg transition-all cursor-pointer',
                    isSelected
                      ? 'bg-rose-600 text-white font-bold shadow-md shadow-rose-900/40'
                      : isCurrent
                        ? 'border border-rose-500/50 text-rose-400 hover:bg-zinc-800'
                        : 'text-zinc-300 hover:bg-zinc-800 hover:text-white',
                    isOutOfRange && 'opacity-20 pointer-events-none',
                  )}
                >
                  {yr}
                </button>
              );
            })}
          </div>
        </div>
      )}

      {error && <p className="text-xs text-red-400 font-medium">{error}</p>}
    </div>
  );
}

