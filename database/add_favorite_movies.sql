USE proyecto7mo;

CREATE TABLE IF NOT EXISTS favorite_movies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  movie_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_movie_favorite (user_id, movie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
