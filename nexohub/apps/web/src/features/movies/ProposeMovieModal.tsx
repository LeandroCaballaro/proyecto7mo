import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { GENRES, type CreateMovieInput } from '@nexohub/shared';
import { Button, Combobox, Dialog, Input, YearPicker } from '../../components/ui';
import { apiClient } from '../../lib/api-client';

export interface ProposeMovieModalProps {
  open: boolean;
  onClose: () => void;
}

export function ProposeMovieModal({ open, onClose }: ProposeMovieModalProps) {
  const queryClient = useQueryClient();

  const [title, setTitle] = useState('');
  const [genreSlug, setGenreSlug] = useState('');
  const [releaseYear, setReleaseYear] = useState<number | null>(new Date().getFullYear());
  const [director, setDirector] = useState('');
  const [synopsis, setSynopsis] = useState('');
  const [posterUrl, setPosterUrl] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});

  const genreOptions = GENRES.map((g) => ({
    value: g.slug,
    label: g.name,
  }));

  const mutation = useMutation({
    mutationFn: async (data: CreateMovieInput) => {
      return apiClient<{ data: { message: string } }>('/movies', {
        method: 'POST',
        body: JSON.stringify(data),
      });
    },
    onSuccess: (res) => {
      toast.success(res.data.message);
      queryClient.invalidateQueries({ queryKey: ['movies'] });
      resetForm();
      onClose();
    },
    onError: (err: any) => {
      if (err.details && Array.isArray(err.details)) {
        const errMap: Record<string, string> = {};
        for (const item of err.details) {
          const field = item.path.replace('body.', '');
          errMap[field] = item.message;
        }
        setErrors(errMap);
      } else {
        toast.error(err.message || 'Error al proponer la película');
      }
    },
  });

  function resetForm() {
    setTitle('');
    setGenreSlug('');
    setReleaseYear(new Date().getFullYear());
    setDirector('');
    setSynopsis('');
    setPosterUrl('');
    setErrors({});
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});

    const newErrors: Record<string, string> = {};
    if (!title.trim()) newErrors.title = 'El título es obligatorio';
    if (!genreSlug) newErrors.genreSlug = 'Debes seleccionar un género';

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    mutation.mutate({
      title: title.trim(),
      genreSlug,
      releaseYear: releaseYear || undefined,
      director: director.trim() || undefined,
      synopsis: synopsis.trim() || undefined,
      posterUrl: posterUrl.trim() || undefined,
    });
  }

  return (
    <Dialog
      open={open}
      onClose={onClose}
      title="Proponer una Película"
      description="Si eres usuario regular pasará a revisión de moderación antes de publicarse en el catálogo."
      maxWidth="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <Input
          label="Título de la Película"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          placeholder="Ej. Interstellar"
          error={errors.title}
        />

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Combobox
            label="Género Cinematográfico"
            options={genreOptions}
            value={genreSlug}
            onChange={setGenreSlug}
            placeholder="Seleccionar género..."
            searchPlaceholder="Buscar género..."
            error={errors.genreSlug}
          />

          <YearPicker
            label="Año de Estreno"
            value={releaseYear}
            onChange={setReleaseYear}
            placeholder="Ej. 2014"
            error={errors.releaseYear}
          />
        </div>

        <Input
          label="Director / Autor"
          value={director}
          onChange={(e) => setDirector(e.target.value)}
          placeholder="Ej. Christopher Nolan"
          error={errors.director}
        />

        <div className="w-full space-y-1.5">
          <label className="block text-xs font-medium text-zinc-300">Sinopsis u Opinión Breve</label>
          <textarea
            rows={3}
            value={synopsis}
            onChange={(e) => setSynopsis(e.target.value)}
            placeholder="Describe la trama o por qué recomiendas esta película..."
            className="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2.5 text-sm text-zinc-100 placeholder-zinc-500 transition-all focus:outline-none focus:border-rose-500/80 focus:ring-2 focus:ring-rose-500/20 resize-none"
          />
        </div>

        <Input
          label="URL del Póster (Opcional)"
          value={posterUrl}
          onChange={(e) => setPosterUrl(e.target.value)}
          placeholder="https://ejemplo.com/poster.jpg"
          error={errors.posterUrl}
        />

        <div className="flex items-center justify-end gap-3 pt-3 border-t border-zinc-800">
          <Button type="button" variant="ghost" onClick={onClose} disabled={mutation.isPending}>
            Cancelar
          </Button>
          <Button type="submit" variant="primary" isLoading={mutation.isPending}>
            Enviar Película
          </Button>
        </div>
      </form>
    </Dialog>
  );
}

