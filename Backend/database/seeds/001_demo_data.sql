USE proyecto7mo;

-- Password for all demo users: password
SET @demo_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT INTO users (id, name, username, email, password_hash, description, profile_image, is_public, role) VALUES
(1, 'Admin NexoHub', 'admin', 'admin@nexohub.local', @demo_password, 'Cuenta administradora para pruebas.', NULL, 1, 'admin'),
(2, 'Carlos Perez', 'carlosp', 'carlos@nexohub.local', @demo_password, 'Fan del cine de aventura y accion.', NULL, 1, 'user'),
(3, 'Ana Gomez', 'anagomez', 'ana@nexohub.local', @demo_password, 'Resenas de drama, romance y comedia.', NULL, 1, 'user'),
(4, 'Maria Lopez', 'marial', 'maria@nexohub.local', @demo_password, 'Me gustan las historias con misterio.', NULL, 1, 'user')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
username = VALUES(username),
description = VALUES(description),
is_public = VALUES(is_public),
role = VALUES(role);

INSERT INTO movies (id, title, genre, featured, description, year, movie_author, user_id) VALUES
(1, 'El Gran Viaje', 'Aventura', 1, 'Una epica aventura entre mundos.', 2021, 'NexoHub', 1),
(2, 'Amor en Paris', 'Romance', 0, 'Drama romantico en Paris.', 2019, 'NexoHub', 1),
(3, 'Risa Mortal', 'Comedia', 1, 'Comedia negra sobre la fama.', 2022, 'NexoHub', 1),
(7, 'Ciudad en Sombras', 'Thriller', 1, 'Una detective sigue pistas imposibles en una ciudad sin descanso.', 2020, 'Lucia Ferrer', 2),
(8, 'Orbitas Perdidas', 'Ciencia ficcion', 1, 'Un equipo intenta volver a casa tras perder comunicacion con la Tierra.', 2023, 'Rafael Mora', 3),
(9, 'La Casa del Lago', 'Misterio', 0, 'Una familia descubre cartas antiguas que cambian su historia.', 2018, 'NexoHub', 4)
ON DUPLICATE KEY UPDATE
title = VALUES(title),
genre = VALUES(genre),
featured = VALUES(featured),
description = VALUES(description),
year = VALUES(year),
movie_author = VALUES(movie_author),
user_id = VALUES(user_id);

INSERT INTO reviewers (id, user_id, name, reputation) VALUES
(1, 1, 'Admin NexoHub', 40),
(2, 2, 'Carlos Perez', 120),
(3, 3, 'Ana Gomez', 95),
(4, 4, 'Maria Lopez', 80)
ON DUPLICATE KEY UPDATE
user_id = VALUES(user_id),
name = VALUES(name),
reputation = VALUES(reputation);

INSERT INTO reviews (user_id, movie_id, rating, image_url, comment) VALUES
(2, 1, 5, NULL, 'Aventura muy entretenida y con gran ritmo.'),
(3, 1, 4, NULL, 'La historia funciona muy bien y los personajes tienen carisma.'),
(4, 7, 5, NULL, 'Tension constante, buen misterio y excelente final.'),
(2, 8, 4, NULL, 'La ambientacion espacial esta muy lograda.'),
(3, 2, 5, NULL, 'Romance clasico con momentos memorables.'),
(4, 9, 4, NULL, 'Misterio pequeno pero muy efectivo.')
ON DUPLICATE KEY UPDATE
rating = VALUES(rating),
image_url = VALUES(image_url),
comment = VALUES(comment);

INSERT INTO review_responses (id, review_id, user_id, rating, comment, created_at)
SELECT 1, r.id, 3, 5, 'Coincido, la aventura sostiene muy bien el ritmo.', NOW()
FROM reviews r
WHERE r.user_id = 2 AND r.movie_id = 1
ON DUPLICATE KEY UPDATE
review_id = VALUES(review_id),
user_id = VALUES(user_id),
rating = VALUES(rating),
comment = VALUES(comment);

INSERT INTO review_responses (id, review_id, user_id, rating, comment, created_at)
SELECT 2, r.id, 2, 4, 'El final tambien me parecio de lo mejor.', NOW()
FROM reviews r
WHERE r.user_id = 4 AND r.movie_id = 7
ON DUPLICATE KEY UPDATE
review_id = VALUES(review_id),
user_id = VALUES(user_id),
rating = VALUES(rating),
comment = VALUES(comment);

INSERT INTO genre_reputation (user_id, genre, points) VALUES
(2, 'Aventura', 25),
(2, 'Ciencia ficcion', 18),
(3, 'Romance', 22),
(3, 'Aventura', 14),
(4, 'Misterio', 20),
(4, 'Thriller', 24)
ON DUPLICATE KEY UPDATE points = VALUES(points);

INSERT INTO favorite_movies (user_id, movie_id) VALUES
(2, 1),
(2, 8),
(3, 2),
(3, 7),
(4, 7),
(4, 9)
ON DUPLICATE KEY UPDATE created_at = created_at;
