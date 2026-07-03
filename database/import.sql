CREATE DATABASE IF NOT EXISTS proyecto7mo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE proyecto7mo;

CREATE TABLE IF NOT EXISTS movies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  genre VARCHAR(100),
  featured TINYINT(1) DEFAULT 0,
  description TEXT,
  year INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  username VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviewers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name VARCHAR(255) NOT NULL,
  reputation INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  movie_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  UNIQUE KEY uk_user_movie (user_id, movie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS genre_reputation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  genre VARCHAR(100) NOT NULL,
  points INT DEFAULT 0,
  UNIQUE KEY uk_user_genre (user_id, genre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO movies (title, genre, featured, description, year) VALUES
('El Gran Viaje', 'Aventura', 1, 'Una épica aventura entre mundos.', 2021),
('Amor en París', 'Romance', 0, 'Drama romántico en París.', 2019),
('Risa Mortal', 'Comedia', 1, 'Comedia negra sobre la fama.', 2022);

INSERT INTO reviewers (user_id, name, reputation) VALUES
(NULL, 'Carlos Pérez', 120),
(NULL, 'Ana Gómez', 95);
