import { useEffect, useRef, useState } from 'react';
import { Check, ChevronDown, ChevronsUpDown, Search, X } from 'lucide-react';
import { cn } from '../../lib/cn';

export interface ComboboxOption {
  value: string;
  label: string;
  description?: string;
  icon?: React.ReactNode;
}

export interface ComboboxProps {
  options: ComboboxOption[];
  value?: string;
  onChange: (val: string) => void;
  placeholder?: string;
  searchPlaceholder?: string;
  label?: string;
  error?: string;
  disabled?: boolean;
  clearable?: boolean;
  searchable?: boolean;
  size?: 'sm' | 'md';
  className?: string;
}

export function Combobox({
  options,
  value,
  onChange,
  placeholder = 'Seleccionar una opción...',
  searchPlaceholder = 'Buscar...',
  label,
  error,
  disabled = false,
  clearable = true,
  searchable = true,
  size = 'md',
  className,
}: ComboboxProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [search, setSearch] = useState('');
  const [activeIndex, setActiveIndex] = useState(0);
  const containerRef = useRef<HTMLDivElement>(null);
  const searchInputRef = useRef<HTMLInputElement>(null);

  const selectedOption = options.find((opt) => opt.value === value);

  const filtered = options.filter(
    (opt) =>
      opt.label.toLowerCase().includes(search.toLowerCase()) ||
      opt.description?.toLowerCase().includes(search.toLowerCase()),
  );

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  useEffect(() => {
    if (isOpen) {
      setSearch('');
      setActiveIndex(0);
      if (searchable) {
        setTimeout(() => searchInputRef.current?.focus(), 50);
      }
    }
  }, [isOpen, searchable]);

  function handleKeyDown(e: React.KeyboardEvent) {
    if (!isOpen) {
      if (e.key === 'Enter' || e.key === 'ArrowDown') {
        e.preventDefault();
        setIsOpen(true);
      }
      return;
    }

    if (e.key === 'Escape') {
      e.preventDefault();
      setIsOpen(false);
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveIndex((prev) => (prev + 1 < filtered.length ? prev + 1 : 0));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveIndex((prev) => (prev - 1 >= 0 ? prev - 1 : filtered.length - 1));
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (filtered[activeIndex]) {
        onChange(filtered[activeIndex].value);
        setIsOpen(false);
      }
    }
  }

  const isSmall = size === 'sm';

  return (
    <div ref={containerRef} className={cn('relative w-full space-y-1', className)}>
      {label && <label className="block text-xs font-semibold text-[#f5f5f5]">{label}</label>}

      {/* Botón trigger principal */}
      <button
        type="button"
        disabled={disabled}
        onClick={() => setIsOpen((prev) => !prev)}
        onKeyDown={handleKeyDown}
        className={cn(
          'w-full flex items-center justify-between gap-2 bg-[#0f172a] border border-[#0f3460] rounded-lg text-left',
          'transition-all duration-200 cursor-pointer',
          'hover:border-[#e94560]/60 focus:outline-none focus:border-[#e94560] focus:ring-1 focus:ring-[#e94560]/30',
          'disabled:opacity-50 disabled:cursor-not-allowed',
          isSmall ? 'px-3 py-1.5 text-xs' : 'px-3.5 py-2.5 text-sm',
          isOpen && 'border-[#e94560] ring-1 ring-[#e94560]/30',
          error && 'border-red-500',
        )}
      >
        <div className="flex items-center gap-2 truncate">
          {selectedOption?.icon}
          <span className={selectedOption ? 'text-[#f5f5f5] font-medium' : 'text-[#a0a0a0]'}>
            {selectedOption ? selectedOption.label : placeholder}
          </span>
        </div>

        <div className="flex items-center gap-1 text-[#a0a0a0]">
          {clearable && selectedOption && !disabled && (
            <span
              role="button"
              tabIndex={0}
              onClick={(e) => {
                e.stopPropagation();
                onChange('');
              }}
              className="p-0.5 hover:text-white transition-colors"
            >
              <X className={cn(isSmall ? 'h-3 w-3' : 'h-3.5 w-3.5')} />
            </span>
          )}
          {isSmall ? (
            <ChevronDown className="h-3.5 w-3.5 shrink-0 text-[#a0a0a0]" />
          ) : (
            <ChevronsUpDown className="h-4 w-4 shrink-0 text-[#a0a0a0]" />
          )}
        </div>
      </button>

      {/* Dropdown flotante con bordes redondeados y estilo NexoHub */}
      {isOpen && (
        <div className="absolute z-50 left-0 right-0 min-w-[180px] mt-1 bg-[#16213e] border border-[#0f3460] rounded-xl shadow-2xl overflow-hidden backdrop-blur-xl animate-in fade-in-0 zoom-in-95 duration-100">
          {/* Campo de búsqueda opcional */}
          {searchable && (
            <div className="flex items-center gap-2 border-b border-[#0f3460] px-3 py-2 bg-[#1a1a2e]/90">
              <Search className="h-3.5 w-3.5 text-[#a0a0a0] shrink-0" />
              <input
                ref={searchInputRef}
                type="text"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value);
                  setActiveIndex(0);
                }}
                onKeyDown={handleKeyDown}
                placeholder={searchPlaceholder}
                className="w-full bg-transparent text-xs text-[#f5f5f5] placeholder-[#a0a0a0] focus:outline-none"
              />
              {search && (
                <button
                  type="button"
                  onClick={() => setSearch('')}
                  className="text-[#a0a0a0] hover:text-white"
                >
                  <X className="h-3 w-3" />
                </button>
              )}
            </div>
          )}

          {/* Lista de opciones filtradas */}
          <div className="max-h-60 overflow-y-auto p-1.5 space-y-0.5 custom-scrollbar">
            {filtered.length === 0 ? (
              <div className="py-4 text-center text-xs text-[#a0a0a0] font-medium">
                No se encontraron opciones.
              </div>
            ) : (
              filtered.map((opt, idx) => {
                const isSelected = opt.value === value;
                const isActive = idx === activeIndex;

                return (
                  <button
                    key={opt.value}
                    type="button"
                    onClick={() => {
                      onChange(opt.value);
                      setIsOpen(false);
                    }}
                    onMouseEnter={() => setActiveIndex(idx)}
                    className={cn(
                      'w-full flex items-center justify-between px-3 py-2 rounded-lg text-left transition-colors cursor-pointer',
                      isSmall ? 'text-xs' : 'text-sm',
                      isActive ? 'bg-[#0f3460] text-white' : 'text-[#cbd5e1]',
                      isSelected && 'text-[#e94560] font-bold bg-[#e94560]/15 border border-[#e94560]/20',
                    )}
                  >
                    <div className="flex items-center gap-2 min-w-0">
                      {opt.icon}
                      <div className="truncate">
                        <p className="truncate font-medium">{opt.label}</p>
                        {opt.description && (
                          <p className="text-[11px] text-[#a0a0a0] truncate">{opt.description}</p>
                        )}
                      </div>
                    </div>
                    {isSelected && <Check className="h-3.5 w-3.5 shrink-0 text-[#e94560]" />}
                  </button>
                );
              })
            )}
          </div>
        </div>
      )}

      {error && <p className="text-xs text-red-400 font-medium">{error}</p>}
    </div>
  );
}
