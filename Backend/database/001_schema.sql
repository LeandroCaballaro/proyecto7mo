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
  external_source VARCHAR(50) NULL,
  external_id VARCHAR(100) NULL,
  poster_url VARCHAR(500) NULL,
  approval_status VARCHAR(20) NOT NULL DEFAULT 'approved',
  rejection_reason TEXT NULL,
  user_id INT NULL,
  KEY idx_movies_title (title),
  KEY idx_movies_genre (genre),
  KEY idx_movies_featured (featured),
  KEY idx_movies_external (external_source, external_id),
  KEY idx_movies_user_id (user_id)
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
  role VARCHAR(20) NOT NULL DEFAULT 'user',
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviewers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name VARCHAR(255) NOT NULL,
  reputation INT DEFAULT 0,
  KEY idx_reviewers_user_id (user_id),
  KEY idx_reviewers_reputation (reputation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  movie_id INT NOT NULL,
  rating TINYINT NOT NULL,
  image_url VARCHAR(255) NULL,
  comment TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_reviews_user_movie (user_id, movie_id),
  KEY idx_reviews_movie_id (movie_id),
  KEY idx_reviews_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS review_responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  review_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NULL,
  comment TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_review_responses_review_id (review_id),
  KEY idx_review_responses_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS review_likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  review_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_review_like (review_id, user_id),
  KEY idx_review_likes_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS genre_reputation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  genre VARCHAR(100) NOT NULL,
  points INT DEFAULT 0,
  UNIQUE KEY uk_user_genre (user_id, genre),
  KEY idx_genre_reputation_user_id (user_id)
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
  expires_at DATETIME NOT NULL,
  KEY idx_api_tokens_user_id (user_id),
  KEY idx_api_tokens_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_password_reset_tokens_user_id (user_id),
  KEY idx_password_reset_tokens_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
