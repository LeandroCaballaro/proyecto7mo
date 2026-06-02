CREATE DATABASE IF NOT EXISTS proyecto7mo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE proyecto7mo;

CREATE TABLE IF NOT EXISTS api_tokens (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS genre_reputation (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  genre VARCHAR(100) NOT NULL,
  points INT DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_genre (user_id, genre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS movies (
  id INT NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  genre VARCHAR(100) DEFAULT NULL,
  featured TINYINT(1) DEFAULT 0,
  description TEXT DEFAULT NULL,
  year INT DEFAULT NULL,
  user_id INT DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS reviewers (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT DEFAULT NULL,
  name VARCHAR(255) NOT NULL,
  reputation INT DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  movie_id INT NOT NULL,
  rating TINYINT(4) NOT NULL,
  comment TEXT DEFAULT NULL,
  media_title VARCHAR(255) DEFAULT NULL,
  poster_url VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  series_id INT DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_movie (user_id, movie_id),
  KEY idx_reviews_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS review_responses (
  id INT NOT NULL AUTO_INCREMENT,
  review_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT(4) NOT NULL,
  comment TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  username VARCHAR(50) DEFAULT NULL,
  profile_image VARCHAR(255) DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT '',
  description VARCHAR(150) DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert data
INSERT INTO api_tokens (id, user_id, token, expires_at) VALUES
(1, 1, '54d189d11fdb62a0ee506d6b5ada76a4b19bcb0490f7eb1dacfebe581e78a187', '2026-05-27 14:34:22'),
(2, 2, '05d4758ecf99c1fcb44b8d38b1d34292f1bddd707cc313f3aeb529de8fad907c', '2026-05-27 19:37:56'),
(3, 3, 'b458e10388c934a42b7397f794e30d8f1b23f5baafb71c3cb0b083971fd6582b', '2026-05-27 21:41:57'),
(4, 4, '6c3192817cebcd63bcd07688cc021d87b722c9d4355d10931e6aee105b118e76', '2026-05-27 21:44:15'),
(5, 5, 'c76f71f412d27fc675893d675ede05b45f4c06c1ce972f4fa34cd6860a6a198b', '2026-05-27 23:15:44'),
(6, 5, 'd945f5f5674fa92c95a70f9b48da263ba3b0b99d439fec70689985ec3562a0d9', '2026-05-27 23:17:49'),
(7, 5, '26e8f2e77e6595c6bb0e305464116e87b65ab79e7e562d281caf7e5ede426834', '2026-05-27 23:37:57'),
(8, 5, 'cedd6a0f46c4d8283865d832c5519d1c9f5a6b26c7437d4b77620f5fa7723af6', '2026-05-27 23:44:28'),
(9, 6, '1c65dedf70df7e3d23920cd3ddbc00c0b8f9d7a5894ddb60cc2b9e23fed7f4db', '2026-05-27 23:50:13'),
(10, 6, '79c043917ab41ce39428f314165e8dac8f7ebd1b70eb05701e9e6ed74394fa0b', '2026-05-27 23:50:51'),
(11, 5, 'f1938bdfe9a3817559fd8a5a3465d9e6c4ba164f747b85f373deeb6b4fafc9ef', '2026-05-28 00:14:32'),
(12, 5, 'f6eb10b26b7526646307c7a9ab757905127b82f03d996f57af78bc014521a488', '2026-05-28 02:23:28'),
(13, 5, 'e29827f6a368418ac5b9abe6827f65ee4b12043f200bb7a355e8fbc631f1bf4d', '2026-05-28 02:45:31'),
(14, 7, '98e8bfe9493e22107f8cb22a1317f80b6590b8080e81f5f772bf105f087d1e9c', '2026-05-28 03:50:24'),
(15, 8, '24e909ae565abb4541fc941576c11c9fd9d531765681342659a1ed71856a6f0a', '2026-05-28 04:13:29'),
(16, 9, 'cfbb2a450ca43c69abed1c97778f1f6a1b80844fb2fb63fb4e0e8aa4201babcb', '2026-05-28 05:28:42'),
(17, 7, '0330e538fe59ea71139207370592ff3196af05c49b79b8964e9c861f37c2583b', '2026-06-02 22:13:46'),
(18, 1, '4ca3958f3d2de471c91ef064d58721e754034a846360a070b28dfac985773045', '2026-06-03 01:08:06'),
(19, 1, '0fef0b3952d11c62371dc1326ff64246dac60e7cd463f9c10cc2e305d3fbf2a7', '2026-06-03 20:22:22'),
(20, 1, 'a9a7a48bc30dccba86fbc78baf48b0cffe377c1afa72ad951275af2f8ba67cc2', '2026-06-04 02:12:56'),
(21, 10, '37d83b8316cb5ffb65ddd643c60d7304b76e1bf90de421945cacec18d9a8eb5d', '2026-06-09 21:52:42');

INSERT INTO genre_reputation (id, user_id, genre, points) VALUES
(1, 7, 'Aventura', 10),
(2, 7, 'Romance', 10),
(3, 1, 'Comedia', 10),
(4, 1, 'Romance', 10),
(5, 10, 'Romance', 10);

INSERT INTO movies (id, title, genre, featured, description, year, user_id) VALUES
(1, 'El Gran Viaje', 'Aventura', 1, 'Una épica aventura entre mundos.', 2021, NULL),
(2, 'Amor en París', 'Romance', 0, 'Drama romántico en París.', 2019, NULL),
(3, 'Risa Mortal', 'Comedia', 1, 'Comedia negra sobre la fama.', 2022, NULL);

INSERT INTO reviewers (id, user_id, name, reputation) VALUES
(1, NULL, 'Carlos Pérez', 120),
(2, NULL, 'Ana Gómez', 95),
(3, 1, 'jere cubas', 20),
(4, 2, 'agustin', 0),
(5, 3, 'prueba', 0),
(6, 4, 'testeo', 0),
(7, 5, 'hola redirigir', 0),
(8, 6, 'ayuda', 0),
(9, 7, 'ayudaaa', 20),
(10, 8, 'abc', 0),
(11, 9, 'ssss', 0),
(12, 3, '', 0),
(13, 2, '', 0),
(14, 1, '', 10),
(15, 10, 'Quiero Espotifai', 10);

INSERT INTO reviews (id, user_id, movie_id, rating, comment, media_title, poster_url, created_at, series_id) VALUES
(1, 7, 1, 5, 'auuu', NULL, NULL, '2026-05-27 01:32:16', NULL),
(2, 1, 1, 5, 'Excelente película', 'Película Excelente', 'https://via.placeholder.com/200x300?text=Película', '2026-05-27 01:32:16', NULL),
(3, 7, 2, 5, 'hola por que se ve tan feo', NULL, NULL, '2026-05-27 01:32:16', NULL),
(4, 1, 3, 5, 'muyu buiena pelicula', 'Muy Buena Película', 'https://via.placeholder.com/200x300?text=Película', '2026-05-27 01:32:16', NULL),
(8, 3, 2, 5, '¡Excelente película! Muy recomendada.', 'Inception', '/img/inception.jpg', '2026-05-27 01:33:48', NULL),
(10, 1, 2, 5, 'muy aburrido borren la pelicula', NULL, NULL, '2026-05-27 01:47:55', NULL),
(11, 10, 2, 5, 'horrinle ', NULL, NULL, '2026-06-02 19:59:00', NULL);

INSERT INTO review_responses (id, review_id, user_id, rating, comment, created_at) VALUES
(1, 8, 1, 3, 'sss', '2026-05-26 22:52:57');

INSERT INTO users (id, name, email, password_hash, username, profile_image, avatar, description) VALUES
(1, 'salchipapa', 'cubasandrea58@gmail.com', '$2y$10$LZ87/gPPzPKgqzilExFLmeN1ycSg94S6gVCSmBQLlmXY8LKRAskT.', NULL, NULL, '/proyecto7mo/Frontend/uploads/avatars/avatar_1.png?v=1779843845', 'rico'),
(2, 'abc', 'abc@gmail.com', '$2y$10$lEeYjAIEzcW5FRsQkeS12OqnAqhM1DtAFaGoIT0XVAsdxafkymJNq', NULL, NULL, '', ''),
(3, 'ssss', 'a8@gmail.com', '$2y$10$KqluPRZWx2QiAwRtpSciIO5qxUEXJmThNKMF0w66fWRxbqxsa.mKK', NULL, NULL, '', ''),
(10, 'sebastian', 'quieroespotifai56@gmail.com', '$2y$10$TXj4Vo9JbbN4ySH2Q5P02.hnp5V/8SXv4FDdy7lPSYQoVfoJshxzq', NULL, '/proyecto7mo/Frontend/uploads/1780429979_Captura de pantalla (224).png', '', '');
