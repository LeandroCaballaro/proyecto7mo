-- Las películas creadas hoy por el importador automático se guardaron como 'imdb'.
-- Se las marca como 'cinemeta' para que el importador pueda depurarlas.
-- Las propuestas de usuarios siempre llegan con reseña inicial (reviews_count > 0) y no se tocan.
UPDATE `movies`
SET `external_source` = 'cinemeta'
WHERE `external_source` = 'imdb'
  AND `reviews_count` = 0
  AND `created_at` >= '2026-11-11 00:00:00';
