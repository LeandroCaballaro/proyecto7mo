import { useEffect, useRef, useState } from 'react';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { cn } from '../../lib/cn';

export interface DatePickerProps {
  value?: Date | null;
  onChange: (date: Date | null) => void;
  label?: string;
  error?: string;
  placeholder?: string;
  minDate?: Date;
  maxDate?: Date;
  disabled?: boolean;
  className?: string;
}

const MONTH_NAMES = [
  'Enero',
  'Febrero',
  'Marzo',
  'Abril',
  'Mayo',
  'Junio',
  'Julio',
  'Agosto',
  'Septiembre',
  'Octubre',
  'Noviembre',
  'Diciembre',
];

const DAY_NAMES = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'];

export function DatePicker({
  value,
  onChange,
  label,
  error,
  placeholder = 'Seleccionar fecha...',
  minDate,
  maxDate,
  disabled = false,
  className,
}: DatePickerProps) {
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const [viewDate, setViewDate] = useState(() => value || new Date());

  useEffect(() => {
    if (value) setViewDate(value);
  }, [value]);

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const currentYear = viewDate.getFullYear();
  const currentMonth = viewDate.getMonth();

  function prevMonth() {
    setViewDate(new Date(currentYear, currentMonth - 1, 1));
  }

  function nextMonth() {
    setViewDate(new Date(currentYear, currentMonth + 1, 1));
  }

  const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
  const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

  const days: (number | null)[] = [];
  for (let i = 0; i < firstDayOfMonth; i++) {
    days.push(null);
  }
  for (let d = 1; d <= daysInMonth; d++) {
    days.push(d);
  }

  const isSelected = (day: number) =>
    value &&
    value.getFullYear() === currentYear &&
    value.getMonth() === currentMonth &&
    value.getDate() === day;

  const isToday = (day: number) => {
    const today = new Date();
    return (
      today.getFullYear() === currentYear &&
      today.getMonth() === currentMonth &&
      today.getDate() === day
    );
  };

  const isDayDisabled = (day: number) => {
    const target = new Date(currentYear, currentMonth, day);
    if (minDate && target < minDate) return true;
    if (maxDate && target > maxDate) return true;
    return false;
  };

  function selectDay(day: number) {
    const picked = new Date(currentYear, currentMonth, day);
    onChange(picked);
    setIsOpen(false);
  }

  const formattedDisplay = value
    ? `${value.getDate()} de ${MONTH_NAMES[value.getMonth()]}, ${value.getFullYear()}`
    : '';

  return (
    <div ref={containerRef} className={cn('relative w-full space-y-1.5', className)}>
      {label && <label className="block text-xs font-semibold text-[#f5f5f5]">{label}</label>}

      {/* Trigger visual */}
      <button
        type="button"
        disabled={disabled}
        onClick={() => setIsOpen((prev) => !prev)}
        className={cn(
          'w-full flex items-center justify-between gap-2 bg-[#16213e] border border-[#0f3460] rounded-lg px-3.5 py-2.5 text-sm text-left',
          'transition-all duration-200 cursor-pointer',
          'focus:outline-none focus:border-[#e94560] focus:ring-1 focus:ring-[#e94560]/30',
          'disabled:opacity-50 disabled:cursor-not-allowed',
          isOpen && 'border-[#e94560] ring-1 ring-[#e94560]/30',
          error && 'border-red-500',
        )}
      >
        <div className="flex items-center gap-2.5">
          <CalendarIcon className="h-4 w-4 text-[#a0a0a0] shrink-0" />
          <span className={value ? 'text-[#f5f5f5] font-medium' : 'text-[#a0a0a0]'}>
            {formattedDisplay || placeholder}
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
            className="p-0.5 text-[#a0a0a0] hover:text-white"
          >
            <X className="h-3.5 w-3.5" />
          </span>
        )}
      </button>

      {/* Calendario desplegable */}
      {isOpen && (
        <div className="absolute z-50 left-0 mt-1 p-3 bg-[#16213e] border border-[#0f3460] rounded-xl shadow-2xl backdrop-blur-xl animate-in fade-in-0 zoom-in-95 duration-100 w-72">
          {/* Header del mes/año con navegación */}
          <div className="flex items-center justify-between mb-3 px-1">
            <span className="text-sm font-bold text-[#f5f5f5]">
              {MONTH_NAMES[currentMonth]} {currentYear}
            </span>
            <div className="flex items-center gap-1">
              <button
                type="button"
                onClick={prevMonth}
                className="p-1 rounded-lg text-[#a0a0a0] hover:text-white hover:bg-[#0f3460] transition-colors cursor-pointer"
              >
                <ChevronLeft className="h-4 w-4" />
              </button>
              <button
                type="button"
                onClick={nextMonth}
                className="p-1 rounded-lg text-[#a0a0a0] hover:text-white hover:bg-[#0f3460] transition-colors cursor-pointer"
              >
                <ChevronRight className="h-4 w-4" />
              </button>
            </div>
          </div>

          {/* Días de la semana */}
          <div className="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-[#a0a0a0] mb-1">
            {DAY_NAMES.map((name) => (
              <div key={name} className="py-1">
                {name}
              </div>
            ))}
          </div>

          {/* Días del mes */}
          <div className="grid grid-cols-7 gap-1 text-center">
            {days.map((day, idx) => {
              if (day === null) {
                return <div key={`empty-${idx}`} className="h-8 w-8" />;
              }

              const selected = isSelected(day);
              const today = isToday(day);
              const disabledDay = isDayDisabled(day);

              return (
                <button
                  key={day}
                  type="button"
                  disabled={disabledDay}
                  onClick={() => selectDay(day)}
                  className={cn(
                    'h-8 w-8 text-xs font-semibold rounded-lg flex items-center justify-center transition-all cursor-pointer',
                    selected
                      ? 'bg-[#e94560] text-white shadow-md shadow-[#e94560]/30'
                      : today
                        ? 'border border-[#e94560]/60 text-[#e94560] hover:bg-[#0f3460]'
                        : 'text-[#f5f5f5] hover:bg-[#0f3460]',
                    disabledDay && 'opacity-20 pointer-events-none',
                  )}
                >
                  {day}
                </button>
              );
            })}
          </div>

          {/* Footer */}
          <div className="mt-3 pt-2 border-t border-[#0f3460] flex justify-between">
            <button
              type="button"
              onClick={() => {
                const now = new Date();
                setViewDate(now);
                onChange(now);
                setIsOpen(false);
              }}
              className="text-xs text-[#e94560] hover:underline font-bold cursor-pointer"
            >
              Hoy
            </button>
            <button
              type="button"
              onClick={() => {
                onChange(null);
                setIsOpen(false);
              }}
              className="text-xs text-[#a0a0a0] hover:text-white font-medium cursor-pointer"
            >
              Limpiar
            </button>
          </div>
        </div>
      )}

      {error && <p className="text-xs text-red-400 font-medium">{error}</p>}
    </div>
  );
}
