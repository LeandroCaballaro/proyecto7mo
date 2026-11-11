import { useState } from 'react';
import { Star } from 'lucide-react';
import { cn } from '../../lib/cn';

export interface StarRatingProps {
  value: number;
  onChange?: (val: number) => void;
  max?: number;
  readOnly?: boolean;
  size?: 'sm' | 'md' | 'lg';
  showNumber?: boolean;
}

export function StarRating({
  value,
  onChange,
  max = 5,
  readOnly = false,
  size = 'md',
  showNumber = false,
}: StarRatingProps) {
  const [hoverVal, setHoverVal] = useState<number | null>(null);

  const sizes = {
    sm: 'h-3.5 w-3.5',
    md: 'h-5 w-5',
    lg: 'h-7 w-7',
  };

  const current = hoverVal !== null ? hoverVal : value;

  return (
    <div className="inline-flex items-center gap-1.5 select-none">
      <div className="flex items-center gap-0.5" onMouseLeave={() => !readOnly && setHoverVal(null)}>
        {Array.from({ length: max }, (_, i) => {
          const starNum = i + 1;
          const isFilled = current >= starNum;
          const isHalf = !isFilled && current >= starNum - 0.5;

          return (
            <button
              key={starNum}
              type="button"
              disabled={readOnly}
              onClick={() => !readOnly && onChange?.(starNum)}
              onMouseEnter={() => !readOnly && setHoverVal(starNum)}
              className={cn(
                'transition-all duration-150',
                readOnly
                  ? 'cursor-default pointer-events-none'
                  : 'cursor-pointer hover:scale-115 active:scale-95 focus:outline-none',
              )}
            >
              <Star
                className={cn(
                  sizes[size],
                  isFilled
                    ? 'fill-amber-400 text-amber-400'
                    : isHalf
                      ? 'fill-amber-400/50 text-amber-400'
                      : 'fill-transparent text-zinc-600',
                )}
              />
            </button>
          );
        })}
      </div>
      {showNumber && (
        <span className="text-xs font-semibold text-amber-400 ml-1">
          {value > 0 ? value.toFixed(1) : 'Sin calif.'}
        </span>
      )}
    </div>
  );
}

