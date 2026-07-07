CREATE DATABASE IF NOT EXISTS proyecto7mo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE proyecto7mo;

CREATE TABLE IF NOT EXISTS movies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  genre VARCHAR(100),
  featured TINYINT(1) DEFAULT 0,
  description TEXT,
  year INT,
  movie_author VARCHAR(255) NULL,
  user_id INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  username VARCHAR(20) NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  description VARCHAR(100) NULL,
  profile_image VARCHAR(255) NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  role VARCHAR(20) NOT NULL DEFAULT 'user'
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
  image_url VARCHAR(255) NULL,
  comment TEXT,
  UNIQUE KEY uk_user_movie (user_id, movie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS review_responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  review_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS genre_reputation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  genre VARCHAR(100) NOT NULL,
  points INT DEFAULT 0,
  UNIQUE KEY uk_user_genre (user_id, genre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS favorite_movies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  movie_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_movie_favorite (user_id, movie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO movies (id, title, genre, featured, description, year, movie_author) VALUES
(1, 'El Gran Viaje', 'Aventura', 1, 'Una epica aventura entre mundos.', 2021, 'NexoHub'),
(2, 'Amor en Paris', 'Romance', 0, 'Drama romantico en Paris.', 2019, 'NexoHub'),
(3, 'Risa Mortal', 'Comedia', 1, 'Comedia negra sobre la fama.', 2022, 'NexoHub')
ON DUPLICATE KEY UPDATE
title = VALUES(title),
genre = VALUES(genre),
featured = VALUES(featured),
description = VALUES(description),
year = VALUES(year),
movie_author = VALUES(movie_author);

INSERT INTO reviewers (id, user_id, name, reputation) VALUES
(1, NULL, 'Carlos Perez', 120),
(2, NULL, 'Ana Gomez', 95)
ON DUPLICATE KEY UPDATE
user_id = VALUES(user_id),
name = VALUES(name),
reputation = VALUES(reputation);
